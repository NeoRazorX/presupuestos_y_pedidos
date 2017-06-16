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

   /**
    * Proveedor del documento de compra
    * @var fs_model
    */
   public $proveedor;
   
   /**
    * Constructor de la clase
    */
   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'compras');
   }

   /**
    * Agrupa las distintas acciones que se pueden realizar al
    * visualizar el controlador
    * @return boolean
    */
   private function acciones() {
      if (null !== filter_input(INPUT_POST, 'buscar_lineas')) {
         $this->buscar_lineas();
         return TRUE;
      }

      if (isset($_REQUEST['buscar_proveedor'])) {
         $this->fbase_buscar_proveedor($_REQUEST['buscar_proveedor']);
         return TRUE;
      }
      
      $ref = filter_input(INPUT_GET, 'ref'); 
      if (isset($ref)) {
         $this->template = 'extension/compras_pedidos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($ref);

         $linea = new linea_pedido_proveedor();
         $this->resultados = $linea->all_from_articulo($ref, $this->offset);
         return TRUE;
      } 
      
      return FALSE;
   }

   /**
    * Método de entrada del controlador
    */
   protected function private_core() {
      parent::private_core();

      $pedido = new pedido_proveedor();
            
      $this->private_core_shared('compras_ped');
      
      if (!$this->acciones()) {
         $this->share_extension();
         $this->init_parametros();

         $this->proveedor = FALSE;

         if (null !== filter_input(INPUT_POST, 'delete'))
            $this->delete_pedido();
         else {
            $mostrar = filter_input(INPUT_GET, 'mostrar'); 
            if (!isset($mostrar) AND ($this->query != '' OR isset($_REQUEST['codagente']) OR isset($_REQUEST['codproveedor']) OR isset($_REQUEST['codserie']))) {
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

   /**
    * Calcula la url válida para el controlador según 
    * los parámetros pasados en la llamada
    * @param mixed $busqueda
    * @return string
    */
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

   /**
    * Calcula la lista de páginas a visualizar según los resultados
    * @return array
    */
   public function paginas() {
      return $this->paginas_shared();
   }

   /**
    * Método para búsqueda de lineas por referencia
    */
   public function buscar_lineas() {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/compras_lineas_pedidos';

      $this->buscar_lineas = filter_input(INPUT_POST, 'buscar_lineas');
      $linea = new linea_pedido_proveedor();
      $codproveedor = filter_input(INPUT_POST, 'codproveedor');
      if (isset($codproveedor))
         $this->lineas = $linea->search_from_proveedor($codproveedor, $this->buscar_lineas, $this->offset);
      else
         $this->lineas = $linea->search($this->buscar_lineas, $this->offset);
   }

   /**
    * Método para el borrado del documento
    */
   private function delete_pedido() {
      $this->delete_shared("pedido_proveedor", FS_PEDIDO);
   }

   /**
    * Método para la inclusión de extensiones de la clase
    */
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

   /**
    * Número de registros con estado pendiente
    * @return int
    */
   public function total_pendientes() {
      return $this->fbase_sql_total('pedidosprov', 'idpedido', 'WHERE idalbaran IS NULL');
   }
   
   /**
    * Número total de registros
    * @return int
    */
   public function total_registros() {
      return $this->fbase_sql_total('pedidosprov', 'idpedido');
   }

   /**
    * Método para filtrar los registros según 
    * los parámetros de búsqueda pasados
    */
   private function buscar($order2) {
      $where = "";
      if($this->proveedor)
         $where .= " AND codproveedor = ".$this->agente->var2str($this->proveedor->codproveedor);
            
      $this->buscar_shared("pedido_proveedor", "pedidosprov", "numproveedor", $where, $order2);
   }
   
   /**
    * Método con la lista de ordenaciones posibles
    * @return array
    */
   public function orden() {
      return $this->orden_shared();
   }  
}