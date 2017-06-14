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
require_model('linea_pedido_cliente.php');
require_model('pais.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');

class ventas_pedido extends fbase_controller {
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

   /**
    * Constructor de la clase
    */
   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDO), 'ventas', FALSE, FALSE);
   }
   
   /**
    * Punto de entrada al controlador
    */
   protected function private_core() {
      parent::private_core();

      $pedido = new pedido_cliente();
      $this->pais = new pais();
      $this->agencia = new agencia_transporte();
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      
      $this->private_core_shared('ventas_pedidos', 'nueva_venta', $this->nuevo_pedido_url);

      if (!$this->get_documento($pedido, 'idpedido', $this->pedido )) {
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " de cliente no encontrado!", 'error', FALSE, FALSE);
         return;
      }
      
      $this->page->title = $this->pedido->codigo;

      /// cargamos el agente
      if (!is_null($this->pedido->codagente)) {
         $agente = new agente();
         $this->agente = $agente->get($this->pedido->codagente);
      }

      /// cargamos el cliente
      $this->cliente_s = $this->cliente->get($this->pedido->codcliente);

      /// comprobamos el pedido
      if ($this->pedido->full_test()) {
         if (isset($_REQUEST['status'])) {
            $this->pedido->status = intval($_REQUEST['status']);

            if ($this->pedido->status == 1 AND is_null($this->pedido->idalbaran)) {
               if ($this->comprobar_stock())
                  $this->generar_documento(
                        'albaran_cliente', 
                        'facturar', 
                        $this->pedido,
                        array('idalbaran'   => NULL,
                              'fechasalida' => NULL)
                  );
               else
                  $this->pedido->status = 0;
            } 
            else {
               if ($this->pedido->save())
                  $this->new_message(ucfirst(FS_PEDIDO) . " modificado correctamente.");
               else
                  $this->new_error_msg("¡Imposible modificar el " . FS_PEDIDO . "!");
            }
         } 
         else {
            if (isset($_GET['nversion']))
               $this->nueva_version();
            else {
               if (isset($_GET['nversionok']))
                  $this->new_message('Esta es la nueva versión del ' . FS_PEDIDO . '.');
            }
         }
      }

      $this->versiones = $this->pedido->get_versiones();
      $this->get_historico();
   }

   /**
    * Método para crear una nueva versión
    * del documento de venta
    */
   private function nueva_version() {
      $pedi = clone $this->pedido;
      $pedi->femail = NULL;
      $pedi->status = 0;
      $this->nueva_version_shared($this->pedido, $pedi);      
   }

   /**
    * Método para obtener la url válida del controlador
    * según los parámetros de la llamada
    * @return string
    */
   public function url() {
      return $this->url_shared($this->pedido);
   }
   
   /**
    * Método para grabar los datos modificados
    * del documento de venta
    */
   private function modificar() {
      $this->pedido->observaciones = $_POST['observaciones'];
      $this->pedido->numero2 = $_POST['numero2'];      
      $serie = $this->serie->get($this->pedido->codserie);

      if ($this->modificar_shared($this->pedido, $serie)) {
         /// ¿cambiamos el cliente?
         if ($_POST['cliente'] != $this->pedido->codcliente)
            $this->cambiar_cliente_shared($this->pedido);
         else {
            $this->update_desde_params($this->pedido);
            $cliente = $this->cliente->get($this->pedido->codcliente);
         }

         if (isset($_POST['numlineas'])) {
            $numlineas = intval($_POST['numlineas']);
            $lineas = $this->pedido->get_lineas();
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
                  /// modificamos la línea
                  if ($value->idlinea == intval($_POST['idlinea_' . $num])) {
                     $encontrada = TRUE;

                     // Calculamos impuesto a aplicar
                     $this->calcular_impuesto_linea($lineas[$k], $serie, $regimeniva, $num);

                     // Actualizamos la linea
                     $this->actualizar_linea_documento($this->pedido, $lineas[$k], $value, $num);
                     break;
                  }
               }

               /// añadimos la línea
               if (!$encontrada AND intval($_POST['idlinea_' . $num]) == -1 AND isset($_POST['referencia_' . $num])) {
                  $linea = new linea_pedido_cliente();
                  $linea->idpedido = $this->pedido->idpedido;
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
                     $this->acumular_linea_documento($this->pedido, $linea);
                  else
                     $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
               }
            }

            /// redondeamos
            $this->redondear_importes_documento($this->pedido);
         }
      }

      if ($this->pedido->save()) {
         $this->new_message(ucfirst(FS_PEDIDO) . " modificado correctamente.");
         $this->new_change(ucfirst(FS_PEDIDO) . ' Cliente ' . $this->pedido->codigo, $this->pedido->url());
      } 
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PEDIDO . "!");
   }
   
   /**
    * Método para obtener la lista de stock de los artículos
    * del documento de venta
    * @return array
    */
   public function get_lineas_stock() {
      return $this->get_lineas_stock_shared($this->pedido, "idpedido");
   }   

   /**
    * Método para calcular la ruta histórica de los documentos dependientes
    * del documento de venta
    */
   private function get_historico() {
      $this->historico = array();
      $orden = 0;

      $this->get_historico_documento($this->historico, "pedido_cliente", "idpedido", $this->pedido->idpedido, $orden);
   }

   /**
    * Comprueba el stock de cada uno de los artículos del pedido.
    * Devuelve TRUE si hay suficiente stock.
    * @return boolean
    */
   private function comprobar_stock() {
      $ok = TRUE;

      $art0 = new articulo();
      foreach ($this->pedido->get_lineas() as $linea) {
         if ($linea->referencia) {
            $articulo = $art0->get($linea->referencia);
            if ($articulo) {
               if (!$articulo->controlstock) {
                  if ($linea->cantidad > $articulo->stockfis) {
                     /// si se pide más cantidad de la disponible, es que no hay suficiente
                     $ok = FALSE;
                  } else {
                     /// comprobamos el stock en el almacén del pedido
                     $ok = FALSE;
                     foreach ($articulo->get_stock() as $stock) {
                        if ($stock->codalmacen == $this->pedido->codalmacen) {
                           if ($stock->cantidad >= $linea->cantidad) {
                              $ok = TRUE;
                           }
                           break;
                        }
                     }
                  }

                  if (!$ok) {
                     $this->new_error_msg('No hay suficiente stock del artículo ' . $linea->referencia);
                     break;
                  }
               }
            }
         }
      }

      return $ok;
   }
}
