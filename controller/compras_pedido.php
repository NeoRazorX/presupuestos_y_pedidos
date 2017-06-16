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

require_model('albaran_proveedor.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('fabricante.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_pedido_proveedor.php');
require_model('pedido_proveedor.php');
require_model('proveedor.php');
require_model('serie.php');

class compras_pedido extends fbase_controller {
   use form_controller;

   /**
    * Proveedor del documento de compra
    * @var fs_model
    */
   public $proveedor;
   public $proveedor_s;

   /**
    * Constructor de la clase
    */
   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDO), 'compras', FALSE, FALSE);
   }
   
   /**
    * Punto de entrada al controlador
    */
   protected function private_core() {
      parent::private_core();
            
      $pedido = new pedido_proveedor();
      $this->proveedor = new proveedor();
      $this->proveedor_s = FALSE;
      
      $this->private_core_shared('compras_pedidos', 'nueva_compra', $this->nuevo_pedido_url);

      /**
       * Primero ejecutamos la función del cron para desbloquear los
       * pedidos de albaranes eliminados y devolverlos al estado original.
       */
      $pedido->cron_job();

      if (!$this->has_documento($pedido, 'idpedido', $this->pedido)) {
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " de proveedor no encontrado!", 'error', FALSE, FALSE);
         return;
      }
      
      $this->page->title = $this->pedido->codigo;

      /// cargamos el agente
      if (!is_null($this->pedido->codagente)) {
         $agente = new agente();
         $this->agente = $agente->get($this->pedido->codagente);
      }

      /// cargamos el proveedor
      $this->proveedor_s = $this->proveedor->get($this->pedido->codproveedor);

      /// comprobamos el pedido
      $this->pedido->full_test();

      $aprobar = filter_input(INPUT_POST, 'aprobar');
      $petid = filter_input(INPUT_POST, 'petid');
      if (isset($aprobar) AND isset($petid) AND is_null($this->pedido->idalbaran)) {
         if ($this->duplicated_petition($petid))
            $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
         else
            $this->generar_documento(
                     'albaran_proveedor', 
                     'aprobar', 
                     $this->pedido,
                     array('idalbaran' => NULL,
                           'editable'  => FALSE)
            );
      }
      else {
         $desbloquear = filter_input(INPUT_GET, 'desbloquear'); 
         if (isset($desbloquear)) {
            $this->pedido->editable = TRUE;
            $this->pedido->save();
         } 
         else {
            $nversion = filter_input(INPUT_GET, 'nversion');
            if (isset($nversion))
               $this->nueva_version();
            else { 
               $nversionok = filter_input(INPUT_GET, 'nversionok');
               if (isset($nversionok))
                  $this->new_message('Esta es la nueva versión del ' . FS_PEDIDO . '.');
            }
         }
      }

      $this->versiones = $this->pedido->get_versiones();
   }

   /**
    * Método para crear una nueva versión
    * del documento de compra
    */
   private function nueva_version() {
      $pedi = clone $this->pedido;
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
    * Método para cambiar el proveedor del
    * documento de compra
    */
   private function cambiar_proveedor() {
      $codproveedor = filter_input(INPUT_POST, 'proveedor');
      $proveedor = $this->proveedor->get($codproveedor);
      if ($proveedor) {
         $this->pedido->codproveedor = $proveedor->codproveedor;
         $this->pedido->nombre = $proveedor->razonsocial;
         $this->pedido->cifnif = $proveedor->cifnif;
      } 
      else {
         $this->pedido->codproveedor = NULL;
         $this->pedido->nombre = filter_input(INPUT_POST, 'nombre');
         $this->pedido->cifnif = filter_input(INPUT_POST, 'cifnif');
      }      
   }
   
   /**
    * Método para grabar los datos modificados
    * del documento de compra
    */
   public function modificar() {
      $this->pedido->observaciones = filter_input(INPUT_POST, 'observaciones');
      $this->pedido->numproveedor = filter_input(INPUT_POST, 'numproveedor');
      $serie = $this->serie->get($this->pedido->codserie);

      if ($this->modificar_shared($this->pedido, $serie)) {
         /// ¿cambiamos el proveedor?
         $codproveedor = filter_input(INPUT_POST, 'proveedor');
         if ($codproveedor != $this->pedido->codproveedor)
            $this->cambiar_proveedor();
         else {
            $this->pedido->nombre = filter_input(INPUT_POST, 'nombre');
            $this->pedido->cifnif = filter_input(INPUT_POST, 'cifnif');
            $proveedor = $this->proveedor->get($this->pedido->codproveedor);
         }

         $numlineas = filter_input(INPUT_POST, 'numlineas');
         if (isset($numlineas)) {
            $numlineas = intval($numlineas);
            $lineas = $this->pedido->get_lineas();
            $articulo = new articulo();

            /// eliminamos las líneas que no encontremos en el $_POST
            $this->eliminar_lineas_borradas($lineas, $numlineas);

            $regimeniva = 'general';
            if ($proveedor)
               $regimeniva = $proveedor->regimeniva;

            // modificamos y/o añadimos las demás líneas
            for ($num = 0; $num <= $numlineas; $num++) {
               $idlinea = filter_input(INPUT_POST, 'idlinea_'.$num);
               if (!isset($idlinea))
                  continue;
               
               $encontrada = FALSE;
               foreach ($lineas as $k => $value) {
                  /// modificamos la línea
                  if ($value->idlinea == intval($idlinea)) {
                     $encontrada = TRUE;
                     
                     // Calculamos impuesto a aplicar
                     $this->calcular_impuesto_linea($lineas[$k], $serie, $regimeniva, $num);
                     
                     // Actualizamos la linea
                     $this->actualizar_linea_documento($this->pedido, $lineas[$k], $value, $num);
                     break;
                  }
               }

               /// añadimos la línea
               $referencia = filter_input(INPUT_POST, 'referencia_' .$num);
               if (!$encontrada AND intval($idlinea) == -1 AND isset($referencia)) {
                  $linea = new linea_pedido_proveedor();
                  $linea->idpedido = $this->pedido->idpedido;
                  $linea->descripcion = filter_input(INPUT_POST, 'desc_' . $num);
                  $linea->irpf = floatval(filter_input(INPUT_POST, 'irpf_' . $num));
                  $linea->cantidad = floatval(filter_input(INPUT_POST, 'cantidad_' . $num));
                  $linea->pvpunitario = floatval(filter_input(INPUT_POST, 'pvp_' . $num));
                  $linea->dtopor = floatval(filter_input(INPUT_POST, 'dto_' . $num));
                  $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                  $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100);

                  // Calculamos impuesto a aplicar
                  $this->calcular_impuesto_linea($linea, $serie, $regimeniva, $num);

                  // Cargamos datos del producto
                  $art0 = $articulo->get($referencia);
                  if ($art0) {
                     $linea->referencia = $art0->referencia;
                     $codcombinacion = filter_input(INPUT_POST, 'codcombinacion_' .$num);
                     if (isset($codcombinacion))
                        $linea->codcombinacion = $codcombinacion;
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
         $this->new_change(ucfirst(FS_PEDIDO) . ' Proveedor ' . $this->pedido->codigo, $this->pedido->url());
      } 
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PEDIDO . "!");
   }
}