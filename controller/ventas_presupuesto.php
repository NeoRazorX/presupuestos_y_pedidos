<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/extras/fbase_controller.php';
require_once __DIR__ . '/form_controller_shared.php';

require_model('agencia_transporte.php');
require_model('albaran_cliente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('fabricante.php');
require_model('factura_cliente.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_presupuesto_cliente.php');
require_model('pais.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');

class ventas_presupuesto extends fbase_controller {
   use form_controller;

   /**
    * Cliente del documento de venta
    * @var fs_model
    */
   public $cliente;
   public $cliente_s;
   
   /**
    * Agencia de transporte del documento de venta
    * @var fs_model
    */
   public $agencia;
   
   /**
    * Lista histórica del documento de venta
    * @var array
    */
   public $historico;
   
   /**
    * País del documento de venta
    * @var fs_model
    */
   public $pais;

   public $setup_validez;
   public $nuevo_presupuesto_url;

   /**
    * Documento de venta con el que se está operando
    * @var fs_model
    */
   public $presupuesto;
   
   /**
    * Constructor de la clase
    */
   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTO), 'ventas', FALSE, FALSE);
   }

   /**
    * Punto de entrada al controlador
    */
   protected function private_core() {
      parent::private_core();

      $presupuesto = new presupuesto_cliente();
      $this->pais = new pais();
      $this->agencia = new agencia_transporte();
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      $this->setup_validez = 30;
      $this->configurar_validez();

      $this->private_core_shared('ventas_presupuestos', 'nueva_venta', $this->nuevo_presupuesto_url);

      if (!$this->get_documento($presupuesto, 'idpresupuesto', $this->presupuesto)) {
         $this->new_error_msg("¡" .ucfirst(FS_PRESUPUESTO)." de cliente no encontrado!", 'error', FALSE, FALSE);
         return;
      }
            
      $this->page->title = $this->presupuesto->codigo;

      /// cargamos el agente
      if (!is_null($this->presupuesto->codagente)) {
         $agente = new agente();
         $this->agente = $agente->get($this->presupuesto->codagente);
      }

      /// cargamos el cliente
      $this->cliente_s = $this->cliente->get($this->presupuesto->codcliente);

      /// comprobamos el presupuesto
      if ($this->presupuesto->full_test()) {
         if (isset($_REQUEST['status'])) {
            $this->presupuesto->status = intval($_REQUEST['status']);

            /// ¿El presupuesto tiene una fecha de validez pasada y queremos ponerlo pendiente?
            if ($this->presupuesto->finoferta() AND intval($_REQUEST['status']) == 0)
               $this->presupuesto->finoferta = date('d-m-Y', strtotime('+' . $this->setup_validez . ' days'));

            if ($this->presupuesto->status == 1 AND is_null($this->presupuesto->idpedido))
               $this->generar_documento(
                        'pedido_cliente', 
                        'fecha', 
                        $this->presupuesto,
                        array('idpedido' => NULL)
               );
            else {
               if ($this->presupuesto->save())
                  $this->new_message(ucfirst(FS_PRESUPUESTO) . " modificado correctamente.");
               else
                  $this->new_error_msg("¡Imposible modificar el " . FS_PRESUPUESTO . "!");
            }
         }
         else {
            if (isset($_GET['nversion']))
               $this->nueva_version();
            else {
               if (isset($_GET['nversionok']))
                  $this->new_message('Esta es la nueva versión del ' . FS_PRESUPUESTO . '.');

               /// Comprobamos las líneas
               $this->check_lineas();
            }
         }
      }

      $this->versiones = $this->presupuesto->get_versiones();
      $this->get_historico();
   }

   /**
    * Método para crear una nueva versión
    * del documento de venta
    */
   private function nueva_version() {
      $presu = clone $this->presupuesto;
      $presu->finoferta = date('d-m-Y', strtotime($presu->fecha . ' +' . $this->setup_validez . ' days'));
      $presu->femail = NULL;
      $presu->status = 0;

      $this->nueva_version_shared($this->presupuesto, $presu);
   }

   /**
    * Comprobamos si los artículos han variado su precio.
    */
   private function check_lineas() {
      if ($this->presupuesto->status == 0 AND $this->presupuesto->coddivisa == $this->empresa->coddivisa) {
         foreach ($this->presupuesto->get_lineas() as $l) {
            if ($l->referencia != '') {
               $data = $this->db->select("SELECT factualizado,pvp FROM articulos WHERE referencia = " . $l->var2str($l->referencia) . " ORDER BY referencia ASC;");
               if ($data) {
                  if (strtotime($data[0]["factualizado"]) > strtotime($this->presupuesto->fecha)) {
                     if ($l->pvpunitario > floatval($data[0]['pvp']))
                        $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                                . " ha bajado desde la elaboración del " . FS_PRESUPUESTO . ".");
                     else {
                        if ($l->pvpunitario < floatval($data[0]['pvp']))
                           $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                                   . " ha subido desde la elaboración del " . FS_PRESUPUESTO . ".");
                     }
                  }
               }
            }
         }
      }
   }

   /**
    * Método para obtener la url válida del controlador
    * según los parámetros de la llamada
    * @return string
    */
   public function url() {
      return $this->url_shared($this->presupuesto);      
   }

   /**
    * Método para grabar los datos modificados
    * del documento de venta
    */
   private function modificar() {
      $this->presupuesto->observaciones = $_POST['observaciones'];
      $this->presupuesto->numero2 = $_POST['numero2'];
      $serie = $this->serie->get($this->presupuesto->codserie);

      if ($this->modificar_shared($this->presupuesto, $serie)) {
         /// ¿cambiamos el cliente?
         if ($_POST['cliente'] != $this->presupuesto->codcliente)
            $this->cambiar_cliente_shared($this->presupuesto);
         else {
            $this->update_desde_params($this->presupuesto);
            $cliente = $this->cliente->get($this->presupuesto->codcliente);
         }

         if (isset($_POST['numlineas'])) {
            $numlineas = intval($_POST['numlineas']);
            $lineas = $this->presupuesto->get_lineas();
            $articulo = new articulo();
            
            /// eliminamos las líneas que no encontremos en el $_POST
            $this->eliminar_lineas_borradas($lineas, $numlineas);

            $regimeniva = 'general';
            if ($cliente)
               $regimeniva = $cliente->regimeniva;

            // modificamos y/o añadimos las demás líneas
            for ($num = 0; $num <= $numlineas; $num++) {
               if (!isset($_POST['idlinea_' . $num]))
                  continue;
               
               $encontrada = FALSE;
               foreach ($lineas as $k => $value) {
                  if ($value->idlinea == intval($_POST['idlinea_' . $num])) {
                     $encontrada = TRUE;

                     // Calculamos impuesto a aplicar
                     $this->calcular_impuesto_linea($lineas[$k], $serie, $regimeniva, $num);

                     // Actualizamos la linea
                     $this->actualizar_linea_documento($this->presupuesto, $lineas[$k], $value, $num);
                     break;
                  }
               }
                  
               if (!$encontrada AND intval($_POST['idlinea_' . $num]) == -1 AND isset($_POST['referencia_' . $num])) {
                  $linea = new linea_presupuesto_cliente();
                  $linea->idpresupuesto = $this->presupuesto->idpresupuesto;
                  $linea->descripcion = $_POST['desc_' . $num];
                  $linea->irpf = floatval($_POST['irpf_' . $num]);
                  $linea->cantidad = floatval($_POST['cantidad_' . $num]);
                  $linea->pvpunitario = floatval($_POST['pvp_' . $num]);
                  $linea->dtopor = floatval($_POST['dto_' . $num]);
                  $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                  $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100);

                  // Calculamos impuesto a aplicar
                  $this->calcular_impuesto_linea($linea, $serie, $regimeniva, $num);

                  // Cargamos datos del producto
                  $art0 = $articulo->get($_POST['referencia_' . $num]);
                  if ($art0) {
                     $linea->referencia = $art0->referencia;
                     if ($_POST['codcombinacion_' . $num])
                        $linea->codcombinacion = $_POST['codcombinacion_' . $num];
                  }

                  // Grabamos la nueva linea
                  if ($linea->save())
                     $this->acumular_linea_documento($this->presupuesto, $linea);
                  else
                     $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
               }                                    
            }
            /// redondeamos
            $this->redondear_importes_documento($this->presupuesto);            
         }
      }
      
      if ($this->presupuesto->save()) {
         $this->new_message(ucfirst(FS_PRESUPUESTO) . " modificado correctamente.");
         $this->new_change(ucfirst(FS_PRESUPUESTO) . ' Cliente ' . $this->presupuesto->codigo, $this->presupuesto->url());
      } 
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PRESUPUESTO . "!");
   }

   private function configurar_validez() {
      $fsvar = new fs_var();
      if (isset($_POST['setup_validez'])) {
         $this->setup_validez = intval($_POST['setup_validez']);
         $fsvar->simple_save('presu_validez', $this->setup_validez);
         $this->new_message('Configuración modificada correctamente.');
      } else {
         $dias = $fsvar->simple_get('presu_validez');
         if ($dias) {
            $this->setup_validez = intval($dias);
         }
      }
   }

   /**
    * Método para obtener la lista de stock de los artículos
    * del documento de venta
    * @return array
    */
   public function get_lineas_stock() {
      return $this->get_lineas_stock_shared($this->presupuesto, "idpresupuesto");
   }

   /**
    * Método para calcular la ruta histórica de los documentos dependientes
    * del documento de venta
    */
   private function get_historico() {
      $this->historico = array();
      $orden = 0;

      if ($this->presupuesto->idpedido)       
         $this->get_historico_documento($this->historico, "", "idpedido", $this->presupuesto->idpedido, $orden, "pedido_cliente");
   }
}
