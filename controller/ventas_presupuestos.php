<?php

/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_model('cliente.php');
require_model('forma_pago.php');
require_model('grupo_clientes.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');

class ventas_presupuestos extends fbase_controller {
   use tree_controller;

   public $cliente;
   public $codgrupo;
   public $codpago;
   public $forma_pago;
   public $grupo;

   public function __construct() {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS), 'ventas');
   }

   private function acciones() {
      if (isset($_POST['buscar_lineas'])) {
         $this->buscar_lineas();
         return TRUE;
      }

      if (isset($_REQUEST['buscar_cliente'])) {
            $this->fbase_buscar_cliente($_REQUEST['buscar_cliente']);
            return TRUE;
      }

      if (isset($_GET['ref'])) {
         $this->template = 'extension/ventas_presupuestos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);

         $linea = new linea_presupuesto_cliente();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
         return TRUE;
      }

      return FALSE;
   }

   protected function private_core() {
      parent::private_core();

      $presupuesto = new presupuesto_cliente();
      $this->forma_pago = new forma_pago();
      $this->grupo = new grupo_clientes();

      $this->private_core_shared('ventas_pres');
      
      if (!$this->acciones()) {
         $this->share_extension();
         $this->init_parametros();

         $this->cliente = FALSE;
         $this->codpago = '';
         $this->codgrupo = '';
               
         if (isset($_POST['rechazar']))
            $this->rechazar();
         else {
            if (isset($_POST['delete']))
               $this->delete_presupuesto();
            else {
               if (!isset($_GET['mostrar']) AND ( isset($_REQUEST['codagente']) OR isset($_REQUEST['codcliente']) OR isset($_REQUEST['codserie']))) {
                  /**
                   * si obtenermos un codagente, un codcliente o un codserie pasamos direcatemente
                   * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                   * entonces nos indica donde tenemos que estar.
                   */
                  $this->mostrar = 'buscar';
               }

               if (isset($_REQUEST['codcliente'])) {
                  if ($_REQUEST['codcliente'] != '') {
                     $cli0 = new cliente();
                     $this->cliente = $cli0->get($_REQUEST['codcliente']);
                  }
               }

               if (isset($_REQUEST['codgrupo']))
                  $this->codgrupo = $_REQUEST['codgrupo'];

               if (isset($_REQUEST['codpago']))
                  $this->codpago = $_REQUEST['codpago'];
               
               $this->obten_parametros();
            }
         }

         /// añadimos segundo nivel de ordenación
         $order2 = $this->obten_segundo_orden($this->order);

         switch ($this->mostrar) {
            case 'rechazados':
            case 'pendientes': {
               if ($this->mostrar == 'rechazados')
                  $this->resultados = $presupuesto->all_rechazados($this->offset, $this->order . $order2);
               else
                 $this->resultados = $presupuesto->all_ptepedir($this->offset, $this->order . $order2);

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
               $this->resultados = $presupuesto->all($this->offset, $this->order . $order2);
               break;
            }
         }

         /**
          * Ejecutamos el proceso del cron para presupuestos.
          * No es estrictamente necesario, pero viene bien para cuando el cliente no tiene configurado el cron.
          */
         $presupuesto->cron_job();
      }
   }
            
   public function url($busqueda = FALSE) {
      $url = $this->url_shared($busqueda);
      if ($busqueda) {
         if ($this->cliente)
            $url .= "&codcliente=" .$this->cliente->codcliente;
         else
            $url .= "&codcliente=";

         $url .= "&codgrupo=" . $this->codgrupo
               . "&codpago=" . $this->codpago
               . "&codserie=" . $this->codserie;
      }
      return $url;
   }

   public function paginas() {
      return $this->paginas_shared();
   }

   public function buscar_lineas() {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/ventas_lineas_presupuestos';

      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_presupuesto_cliente();

      if (isset($_POST['codcliente']))
         $this->lineas = $linea->search_from_cliente2($_POST['codcliente'], $this->buscar_lineas, $_POST['buscar_lineas_o'], $this->offset);
      else
         $this->lineas = $linea->search($this->buscar_lineas, $this->offset);
   }

   private function delete_presupuesto() {
      $this->delete_shared("presupuesto_cliente", FS_PRESUPUESTO);
   }

   private function share_extension() {
      /// añadimos las extensiones para clientes, agentes y artículos
      $extensiones[] = array(
            'name' => 'presupuestos_cliente',
            'page_from' => __CLASS__,
            'page_to' => 'ventas_cliente',
            'type' => 'button',
            'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst(FS_PRESUPUESTOS),
            'params' => '');
      
      $this->share_extension_shared($extensiones, 'presupuestos', FS_PRESUPUESTOS);
   }

   public function total_pendientes() {
      return $this->fbase_sql_total('presupuestoscli', 'idpresupuesto', 'WHERE idpedido IS NULL AND status = 0');
   }

   public function total_rechazados() {
      return $this->fbase_sql_total('presupuestoscli', 'idpresupuesto', 'WHERE status = 2');
   }

   private function total_registros() {
      return $this->fbase_sql_total('presupuestoscli', 'idpresupuesto');
   }

   private function buscar($order2) {
      $where = "";
      if ($this->cliente)
         $where .= " AND codcliente = " . $this->agente->var2str($this->cliente->codcliente);

      /* FIXME: Quitar "Select IN" por "Select EXISTS" */
      if ($this->codgrupo != '')
         $where .= " AND codcliente IN (SELECT codcliente FROM clientes WHERE codgrupo = " . $this->agente->var2str($this->codgrupo) . ")";

      if ($this->codpago != '')
         $where .= " AND codpago = " . $this->agente->var2str($this->codpago);
      
      $this->buscar_shared("presupuesto_cliente", "presupuestoscli", "numero2", $where, $order2);
   }

   private function rechazar() {
      $pre0 = new presupuesto_cliente();
      $num = 0;
      $offset = 0;
      $presupuestos = $pre0->all_ptepedir();
      while ($presupuestos) {                     // FIXME: Es necesario el while ????
         foreach ($presupuestos as $pre) {
            if (strtotime($pre->fecha) < strtotime($_POST['rechazar'])) {
               $pre->status = 2;
               $pre->save();
               $num++;
            }

            $offset++;
         }

         $presupuestos = $pre0->all_ptepedir($offset);
      }

      $this->new_message($num . ' ' . FS_PRESUPUESTOS . ' rechazados.');
   }

   public function orden() {
      $order = array('finoferta_desc' => array(
                           'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
                           'texto' => 'Validez',
                           'orden' => 'finoferta DESC'),
         
                     'finoferta_asc' => array(
                           'icono' => '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>',
                           'texto' => 'Validez',
                           'orden' => 'finoferta ASC')
               );
      return array_merge($order, $this->orden_shared());
   }
}