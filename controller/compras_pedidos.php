<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('agente.php');
require_model('articulo.php');
require_model('proveedor.php');
require_model('pedido_proveedor.php');

class compras_pedidos extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $offset;
   public $resultados;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'compras');
   }

   protected function private_core()
   {
      $pedido = new pedido_proveedor();

      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }

      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else if( isset($_GET['codagente']) )
      {
         $this->template = 'extension/compras_pedidos_agente';

         $agente = new agente();
         $this->agente = $agente->get($_GET['codagente']);
         $this->resultados = $pedido->all_from_agente($_GET['codagente'], $this->offset);
      }
      else if( isset($_GET['codproveedor']) )
      {
         $this->template = 'extension/compras_pedidos_proveedor';

         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_GET['codproveedor']);
         $this->resultados = $pedido->all_from_proveedor($_GET['codproveedor'], $this->offset);
      }
      else if( isset($_GET['ref']) )
      {
         $this->template = 'extension/compras_pedidos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);

         $linea = new linea_pedido_proveedor();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
      }
      else
      {
         $this->share_extension();

         if( isset($_POST['delete']) )
         {
            $this->delete_pedido();
         }
         
         /// ejecutamos el proceso del cron para pedidos.
         $pedido->cron_job();
         
         if($this->query)
         {
            $this->resultados = $pedido->search($this->query, $this->offset);
         }
         else if( isset($_GET['pendientes']) )
         {
            $this->resultados = $pedido->all_ptealbaran($this->offset);
         }
         else
         {
            $this->resultados = $pedido->all($this->offset);
         }
      }
   }
   
   public function paginas()
   {
      $url = $this->url();

      if( isset($_GET['pendientes']) )
      {
         $url .= '&pendientes=TRUE';
      }
      else if( isset($_GET['codagente']) )
      {
         $url .= '&codagente=' . $_GET['codagente'];
      }
      else if( isset($_GET['codproveedor']) )
      {
         $url .= '&codproveedor=' . $_GET['codproveedor'];
      }
      else if( isset($_GET['ref']) )
      {
         $url .= '&ref=' . $_GET['ref'];
      }
      
      $paginas = array();
      $i = 0;
      $num = 0;
      $actual = 1;
      
      if( isset($_GET['pendientes']) )
      {
         $total = $this->total_pendientes();
      }
      else
      {
         $total = $this->total_resultados();
      }
      
      /// añadimos todas la página
      while($num < $total)
      {
         $paginas[$i] = array(
             'url' => $url."&offset=".($i*FS_ITEM_LIMIT),
             'num' => $i + 1,
             'actual' => ($num == $this->offset)
         );
         
         if($num == $this->offset)
         {
            $actual = $i;
         }
         
         $i++;
         $num += FS_ITEM_LIMIT;
      }
      
      /// ahora descartamos
      foreach($paginas as $j => $value)
      {
         $enmedio = intval($i/2);
         
         /**
          * descartamos todo excepto la primera, la última, la de enmedio,
          * la actual, las 5 anteriores y las 5 siguientes
          */
         if( ($j>1 AND $j<$actual-5 AND $j!=$enmedio) OR ($j>$actual+5 AND $j<$i-1 AND $j!=$enmedio) )
         {
            unset($paginas[$j]);
         }
      }
      
      if( count($paginas) > 1 )
      {
         return $paginas;
      }
      else
      {
         return array();
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';

      if( isset($_GET['pendientes']) )
      {
         $extra = '&pendientes=TRUE';
      }
      else if( isset($_GET['codagente']) )
      {
         $extra = '&codagente=' . $_GET['codagente'];
      }
      else if( isset($_GET['codproveedor']) )
      {
         $extra = '&codproveedor=' . $_GET['codproveedor'];
      }
      else if( isset($_GET['ref']) )
      {
         $extra = '&ref=' . $_GET['ref'];
      }

      if($this->query != '' AND $this->offset > '0')
      {
         $url = $this->url() . "&query=" . $this->query . "&offset=" . ($this->offset - FS_ITEM_LIMIT) . $extra;
      }
      else if($this->query == '' AND $this->offset > '0')
      {
         $url = $this->url() . "&offset=" . ($this->offset - FS_ITEM_LIMIT) . $extra;
      }

      return $url;
   }

   public function siguiente_url()
   {
      $url = '';
      $extra = '';

      if( isset($_GET['pendientes']) )
      {
         $extra = '&pendientes=TRUE';
      }
      else if( isset($_GET['codagente']) )
      {
         $extra = '&codagente=' . $_GET['codagente'];
      }
      else if( isset($_GET['codproveedor']) )
      {
         $extra = '&codproveedor=' . $_GET['codproveedor'];
      }
      else if( isset($_GET['ref']) )
      {
         $extra = '&ref=' . $_GET['ref'];
      }

      if($this->query != '' AND count($this->resultados) == FS_ITEM_LIMIT)
      {
         $url = $this->url() . "&query=" . $this->query . "&offset=" . ($this->offset + FS_ITEM_LIMIT) . $extra;
      }
      else if($this->query == '' AND count($this->resultados) == FS_ITEM_LIMIT)
      {
         $url = $this->url() . "&offset=" . ($this->offset + FS_ITEM_LIMIT) . $extra;
      }

      return $url;
   }

   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/compras_lineas_pedidos';

      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_pedido_proveedor();
      $this->lineas = $linea->search($this->buscar_lineas);
   }

   private function delete_pedido()
   {
      $ped0 = new pedido_proveedor();
      $pedido = $ped0->get($_POST['delete']);
      if($pedido)
      {
         if( $pedido->delete() )
         {
            $this->clean_last_changes();
         }
         else
            $this->new_error_msg("¡Imposible eliminar el " . FS_PEDIDO . "!");
      }
      else
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " no encontrado!");
   }

   private function share_extension()
   {
      /// añadimos las extensiones para proveedors, agentes y artículos
      $extensiones = array(
          array(
              'name' => 'pedidos_proveedor',
              'page_from' => __CLASS__,
              'page_to' => 'compras_proveedor',
              'type' => 'button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PEDIDOS),
              'params' => ''
          ),
          array(
              'name' => 'pedidos_agente',
              'page_from' => __CLASS__,
              'page_to' => 'admin_agente',
              'type' => 'button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PEDIDOS) . ' a proveedor',
              'params' => ''
          ),
          array(
              'name' => 'pedidos_articulo',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_articulo',
              'type' => 'tab_button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PEDIDOS) . ' a proveedor',
              'params' => ''
          ),
      );
      foreach ($extensiones as $ext)
      {
         $fsext0 = new fs_extension($ext);
         if (!$fsext0->save())
         {
            $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
         }
      }
   }
   
   private function total_resultados()
   {
      $data = $this->db->select("SELECT COUNT(*) as total FROM pedidosprov;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
      {
         return 0;
      }
   }
   
   public function total_pendientes()
   {
      $data = $this->db->select("SELECT COUNT(idpedido) as total FROM pedidosprov WHERE idalbaran IS NULL;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
}
