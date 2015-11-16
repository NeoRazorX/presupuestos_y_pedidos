<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('agente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('presupuesto_cliente.php');
require_model('serie.php');

class ventas_presupuestos extends fs_controller
{
   public $agente;
   public $articulo;
   public $buscar_lineas;
   public $cliente;
   public $codagente;
   public $codserie;
   public $desde;
   public $hasta;
   public $lineas;
   public $mostrar;
   public $num_resultados;
   public $offset;
   public $order;
   public $resultados;
   public $serie;
   public $total_resultados;
   public $total_resultados_txt;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS).' de cliente', 'ventas', FALSE, TRUE, TRUE);
   }

   protected function private_core()
   {
      $presupuesto = new presupuesto_cliente();
      $this->agente = new agente();
      $this->serie = new serie();

      $this->mostrar = 'todo';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
         setcookie('ventas_pres_mostrar', $this->mostrar, time()+FS_COOKIES_EXPIRE);
      }
      else if( isset($_COOKIE['ventas_pres_mostrar']) )
      {
         $this->mostrar = $_COOKIE['ventas_pres_mostrar'];
      }
      
      $this->offset = 0;
      if( isset($_REQUEST['offset']) )
      {
         $this->offset = intval($_REQUEST['offset']);
      }
      
      $this->order = 'fecha DESC';
      if( isset($_GET['order']) )
      {
         if($_GET['order'] == 'fecha_desc')
         {
            $this->order = 'fecha DESC';
         }
         else if($_GET['order'] == 'fecha_asc')
         {
            $this->order = 'fecha ASC';
         }
         else if($_GET['order'] == 'codigo_desc')
         {
            $this->order = 'codigo DESC';
         }
         else if($_GET['order'] == 'codigo_asc')
         {
            $this->order = 'codigo ASC';
         }
         
         setcookie('ventas_pres_order', $this->order, time()+FS_COOKIES_EXPIRE);
      }
      else if( isset($_COOKIE['ventas_pres_order']) )
      {
         $this->order = $_COOKIE['ventas_pres_order'];
      }
      
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if( isset($_GET['ref']) )
      {
         $this->template = 'extension/ventas_presupuestos_articulo';

         $articulo = new articulo();
         $this->articulo = $articulo->get($_GET['ref']);

         $linea = new linea_presupuesto_cliente();
         $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
      }
      else
      {
         $this->share_extension();
         $this->cliente = FALSE;
         $this->codagente = '';
         $this->codserie = '';
         $this->desde = '';
         $this->hasta = '';
         $this->num_resultados = '';
         $this->total_resultados = '';
         $this->total_resultados_txt = '';

         if( isset($_POST['delete']) )
         {
            $this->delete_presupuesto();
         }
         else
         {
            if( !isset($_GET['mostrar']) AND (isset($_REQUEST['codagente']) OR isset($_REQUEST['codcliente']) OR isset($_REQUEST['codserie'])) )
            {
               /**
                * si obtenermos un codagente, un codcliente o un codserie pasamos direcatemente
                * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                * entonces nos indica donde tenemos que estar.
                */
               $this->mostrar = 'buscar';
            }
            
            if( isset($_REQUEST['codcliente']) )
            {
               if($_REQUEST['codcliente'] != '')
               {
                  $cli0 = new cliente();
                  $this->cliente = $cli0->get($_REQUEST['codcliente']);
               }
            }
            
            if( isset($_REQUEST['codagente']) )
            {
               $this->codagente = $_REQUEST['codagente'];
            }
            
            if( isset($_REQUEST['codserie']) )
            {
               $this->codserie = $_REQUEST['codserie'];
            }
            
            if( isset($_REQUEST['desde']) )
            {
               $this->codserie = $_REQUEST['codserie'];
               $this->desde = $_REQUEST['desde'];
               $this->hasta = $_REQUEST['hasta'];
            }
         }
         
         /// añadimos segundo nivel de ordenación
         $order2 = '';
         if($this->order == 'fecha DESC')
         {
            $order2 = ', codigo DESC';
         }
         else if($this->order == 'fecha ASC')
         {
            $order2 = ', codigo ASC';
         }
         
         if($this->mostrar == 'pendientes')
         {
            $this->resultados = $presupuesto->all_ptepedir($this->offset, $this->order.$order2);
            
            if($this->offset == 0)
            {
               $this->total_resultados = 0;
               $this->total_resultados_txt = 'Suma total de esta página:';
               foreach($this->resultados as $alb)
               {
                  $this->total_resultados += $alb->total;
               }
            }
         }
         else if($this->mostrar == 'rechazados')
         {
            $this->resultados = $presupuesto->all_rechazados($this->offset, $this->order.$order2);
            
            if($this->offset == 0)
            {
               $this->total_resultados = 0;
               $this->total_resultados_txt = 'Suma total de esta página:';
               foreach($this->resultados as $alb)
               {
                  $this->total_resultados += $alb->total;
               }
            }
         }
         else if($this->mostrar == 'buscar')
         {
            $this->buscar($order2);
         }
         else
         {
            /// ejecutamos la tarea del cron
            $presupuesto->cron_job();
            $this->resultados = $presupuesto->all($this->offset, $this->order.$order2);
         }
      }
   }
   
   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $cli0 = new cliente();
      $json = array();
      foreach($cli0->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }
   
   public function paginas()
   {
      $codcliente = '';
      if($this->cliente)
      {
         $codcliente = $this->cliente->codcliente;
      }
      
      $url = $this->url()."&mostrar=".$this->mostrar
              ."&query=".$this->query
              ."&codserie=".$this->codserie
              ."&codagente=".$this->codagente
              ."&codcliente=".$codcliente
              ."&desde=".$this->desde
              ."&hasta=".$this->hasta;
      
      $paginas = array();
      $i = 0;
      $num = 0;
      $actual = 1;
      
      if($this->mostrar == 'pendientes')
      {
         $total = $this->total_pendientes();
      }
      else if($this->mostrar == 'rechazados')
      {
         $total = $this->total_rechazados();
      }
      else if($this->mostrar == 'buscar')
      {
         $total = $this->num_resultados;
      }
      else
      {
         $total = $this->total_registros();
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

   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/ventas_lineas_presupuestos';

      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_presupuesto_cliente();
      
      if( isset($_POST['codcliente']) )
      {
         $this->lineas = $linea->search_from_cliente2($_POST['codcliente'], $this->buscar_lineas, $_POST['buscar_lineas_o'], $this->offset);
      }
      else
      {
         $this->lineas = $linea->search($this->buscar_lineas, $this->offset);
      }
   }

   private function delete_presupuesto()
   {
      $pre = new presupuesto_cliente();
      $pre1 = $pre->get($_POST['delete']);
      if ($pre1)
      {
         if ($pre1->delete())
         {
            $this->new_message(ucfirst(FS_PRESUPUESTO) . " " . $pre1->codigo . " borrado correctamente.");
         }
         else
            $this->new_error_msg("¡Imposible borrar el " . FS_PRESUPUESTO . "!");
      }
      else
         $this->new_error_msg("¡" . ucfirst(FS_PRESUPUESTO) . " no encontrado!");
   }

   private function share_extension()
   {
      /// añadimos las extensiones para clientes, agentes y artículos
      $extensiones = array(
          array(
              'name' => 'presupuestos_cliente',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_cliente',
              'type' => 'button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PRESUPUESTOS),
              'params' => ''
          ),
          array(
              'name' => 'presupuestos_agente',
              'page_from' => __CLASS__,
              'page_to' => 'admin_agente',
              'type' => 'button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PRESUPUESTOS) . ' de cliente',
              'params' => ''
          ),
          array(
              'name' => 'presupuestos_articulo',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_articulo',
              'type' => 'tab_button',
              'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; '.ucfirst(FS_PRESUPUESTOS) . ' de cliente',
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
   
   public function total_pendientes()
   {
      $data = $this->db->select("SELECT COUNT(idpresupuesto) as total FROM presupuestoscli WHERE idpedido IS NULL AND status=0;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
   public function total_rechazados()
   {
      $data = $this->db->select("SELECT COUNT(idpresupuesto) as total FROM presupuestoscli WHERE status=2;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   private function total_registros()
   {
      $data = $this->db->select("SELECT COUNT(idpresupuesto) as total FROM presupuestoscli;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
   private function buscar($order2)
   {
      $this->resultados = array();
      $this->num_resultados = 0;
      $query = $this->agente->no_html( strtolower($this->query) );
      $sql = " FROM presupuestoscli ";
      $where = 'WHERE ';
      
      if($this->query != '')
      {
         $sql .= $where;
         if( is_numeric($query) )
         {
            $sql .= "(codigo LIKE '%".$query."%' OR numero2 LIKE '%".$query."%' OR observaciones LIKE '%".$query."%')";
         }
         else
         {
            $sql .= "(lower(codigo) LIKE '%".$query."%' OR lower(numero2) LIKE '%".$query."%' "
                    . "OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%')";
         }
         $where = ' AND ';
      }
      
      if($this->codagente != '')
      {
         $sql .= $where."codagente = ".$this->agente->var2str($this->codagente);
         $where = ' AND ';
      }
      
      if($this->cliente)
      {
         $sql .= $where."codcliente = ".$this->agente->var2str($this->cliente->codcliente);
         $where = ' AND ';
      }
      
      if($this->codserie != '')
      {
         $sql .= $where."codserie = ".$this->agente->var2str($this->codserie);
         $where = ' AND ';
      }
      
      if($this->desde != '')
      {
         $sql .= $where."fecha >= ".$this->agente->var2str($this->desde);
         $where = ' AND ';
      }
      
      if($this->hasta != '')
      {
         $sql .= $where."fecha <= ".$this->agente->var2str($this->hasta);
         $where = ' AND ';
      }
      
      $data = $this->db->select("SELECT COUNT(idpresupuesto) as total".$sql);
      if($data)
      {
         $this->num_resultados = intval($data[0]['total']);
         
         $data2 = $this->db->select_limit("SELECT *".$sql." ORDER BY ".$this->order.$order2, FS_ITEM_LIMIT, $this->offset);
         if($data2)
         {
            foreach($data2 as $d)
            {
               $this->resultados[] = new presupuesto_cliente($d);
            }
         }
         
         $data2 = $this->db->select("SELECT SUM(total) as total".$sql);
         if($data2)
         {
            $this->total_resultados = floatval($data2[0]['total']);
            $this->total_resultados_txt = 'Suma total de los resultados:';
         }
      }
   }
}
