<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
require_once __DIR__ . '/tree_controller_shared.php';

require_model('agente.php');
require_model('articulo.php');
require_model('proveedor.php');
require_model('pedido_proveedor.php');

class compras_pedidos extends fbase_controller {
   use tree_controller;

   public $proveedor;
   
   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'compras');
   }

   private function acciones() {
      if (isset($_POST['buscar_lineas'])) {
         $this->buscar_lineas();
         return TRUE;
      }

      if (isset($_REQUEST['buscar_proveedor'])) {
         $this->fbase_buscar_proveedor($_REQUEST['buscar_proveedor']);
         return TRUE;
      }
         
      if (isset($_GET['ref'])) {
         $this->template = 'extension/compras_pedidos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);

         $linea = new linea_pedido_proveedor();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
         return TRUE;
      } 
      
      return FALSE;
   }

   protected function private_core() {
      parent::private_core();

      $pedido = new pedido_proveedor();
            
      $this->private_core_shared('compras_ped');
      
      if (!$this->acciones()) {
         $this->share_extension();
         $this->init_parametros();

         $this->proveedor = FALSE;

         if (isset($_POST['delete']))
            $this->delete_pedido();
         else {
            if (!isset($_GET['mostrar']) AND ( $this->query != '' OR isset($_REQUEST['codagente']) OR isset($_REQUEST['codproveedor']) OR isset($_REQUEST['codserie']))) {
               /**
                * si obtenermos un codagente, un codproveedor o un codserie pasamos direcatemente
                * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                * entonces nos indica donde tenemos que estar.
                */
               $this->mostrar = 'buscar';
            }

            if (isset($_REQUEST['codproveedor'])) {
               if ($_REQUEST['codproveedor'] != '') {
                  $pro0 = new proveedor();
                  $this->proveedor = $pro0->get($_REQUEST['codproveedor']);
               }
            }

            $this->obten_parametros();
         }

         /// añadimos segundo nivel de ordenación
         $order2 = $this->obten_segundo_orden($this->order);

         // lanzamos la accion de consulta
         switch ($this->mostrar) {
            case 'pendientes': {
               $this->resultados = $pedido->all_ptealbaran($this->offset, $this->order . $order2);
               if ($this->offset == 0) {
                  /// calculamos el total, pero desglosando por divisa
                  $this->total_resultados = array();
                  $this->total_resultados_txt = 'Suma total de esta página:';
                  $this->total_resultados = $this->total_por_divisa($this->resultados);
               }
               break;
            }

            case 'buscar': {
               $this->buscar($order2);
               break;
            }

            default: {
               $this->resultados = $pedido->all($this->offset, $this->order . $order2);
               break;
            }
         }

         /**
          * Ejecutamos el proceso del cron para pedidos.
          * No es estrictamente necesario, pero viene bien para cuando el
          * cliente no tiene configurado el cron.
          */
         $pedido->cron_job();
      }
   }

   public function url($busqueda = FALSE) {
      $url = $this->url_shared($busqueda);
      if ($busqueda) {
         if ($this->proveedor)
            $url .= "&codproveedor=" .$this->proveedor->codproveedor;
         else
            $url .= "&codproveedor=";
      }
      return $url;      
   }

   public function paginas() {
      return $this->paginas_shared();
   }

   public function buscar_lineas() {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/compras_lineas_pedidos';

      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_pedido_proveedor();

      if (isset($_POST['codproveedor']))
         $this->lineas = $linea->search_from_proveedor($_POST['codproveedor'], $this->buscar_lineas, $this->offset);
      else
         $this->lineas = $linea->search($this->buscar_lineas, $this->offset);
   }

   private function delete_pedido() {
      $this->delete_shared("pedido_proveedor", FS_PEDIDO);
   }

   private function share_extension() {
      /// añadimos las extensiones para proveedors, agentes y artículos
      $extensiones[] = array(
            'name' => 'pedidos_proveedor',
            'page_from' => __CLASS__,
            'page_to' => 'compras_proveedor',
            'type' => 'button',
            'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst(FS_PEDIDOS),
            'params' => '');
      
      $this->share_extension_shared($extensiones, 'pedidos', FS_PEDIDOS);
   }

   public function total_pendientes()
   {
      return $this->fbase_sql_total('pedidosprov', 'idpedido', 'WHERE idalbaran IS NULL');
   }
   
   private function total_registros()
   {
      return $this->fbase_sql_total('pedidosprov', 'idpedido');
   }

   private function buscar($order2) {
      $where = "";
      if($this->proveedor)
         $where .= " AND codproveedor = ".$this->agente->var2str($this->proveedor->codproveedor);
            
      $this->buscar_shared("pedido_proveedor", "pedidosprov", "numproveedor", $where, $order2);
   }
   
   public function orden() {
      return $this->orden_shared();
   }  
}