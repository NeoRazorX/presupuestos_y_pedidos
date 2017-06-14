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

/**
 * Procedures and shared utilities
 *
 * @author Artex Trading sa (2017) <jcuello@artextrading.com>
 */

require_model('agente.php');

trait tree_controller {
      
   public $lineas;
   public $serie;
   public $agente;
   public $almacenes;
   public $articulo;
   public $codagente;
   public $codalmacen;
   public $codserie;
   public $desde;
   public $hasta;
   public $mostrar;
   public $buscar_lineas;
   public $num_resultados;
   public $offset;
   public $order;
   public $resultados;
   public $total_resultados;
   public $total_resultados_txt;      
   
   private function total_por_divisa($data) {
      $result = array();
      foreach ($data as $record) {
         if (!isset($result[$record->coddivisa]))
            $result[$record->coddivisa] = array('coddivisa' => $record->coddivisa, 'total' => 0);

         $result[$record->coddivisa]['total'] += $record->total;
      }
      return $result;
   }
      
   private function obten_segundo_orden($order) {
      switch ($order) {
         case 'fecha DESC': {
            $result = ', hora DESC';
            break;
         }

         case 'fecha ASC': {
            $result = ', hora ASC';
            break;
         }

         case 'finoferta ASC':
         case 'finoferta DESC':
         case 'fechasalida ASC':
         case 'fechasalida DESC': {
            if (strtolower(FS_DB_TYPE) == 'postgresql')
               $result = ' NULLS LAST';
            else
               $result = '';
            break;
         }
         
         default: {
            $result = '';
            break;
         }
      }      
      return $result;
   }
   
   private function init_parametros() {
      $this->codagente = '';
      $this->codalmacen = '';
      $this->codserie = '';
      $this->desde = '';
      $this->hasta = '';
      $this->num_resultados = '';
      $this->total_resultados = array();
      $this->total_resultados_txt = '';      
   }

   private function obten_parametros() {
      if (isset($_REQUEST['codagente']))
         $this->codagente = $_REQUEST['codagente'];

      if (isset($_REQUEST['codalmacen']))
         $this->codalmacen = $_REQUEST['codalmacen'];

      if (isset($_REQUEST['codserie']))
         $this->codserie = $_REQUEST['codserie'];

      if (isset($_REQUEST['desde'])) {
         $this->codserie = $_REQUEST['codserie'];
         $this->desde = $_REQUEST['desde'];
         $this->hasta = $_REQUEST['hasta'];
      }      
   }
   
   protected function private_core_shared($id) {
      $this->agente = new agente();
      $this->almacenes = new almacen();
      $this->serie = new serie();

      if (isset($_GET['mostrar'])) {
         $this->mostrar = $_GET['mostrar'];
         setcookie($id.'_mostrar', $this->mostrar, time() + FS_COOKIES_EXPIRE);
      }
      else {
         if (isset($_COOKIE[$id.'_mostrar']))
            $this->mostrar = $_COOKIE[$id.'_mostrar'];
         else
            $this->mostrar = 'todo';
      }

      if (isset($_GET['offset']))
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;

      $this->order = 'fecha DESC';
      if (isset($_GET['order'])) {
         $orden_l = $this->orden();
         if (isset($orden_l[$_GET['order']])) {
            $this->order = $orden_l[$_GET['order']]['orden'];
         }

         setcookie($id.'_order', $this->order, time() + FS_COOKIES_EXPIRE);
      }
      else {
         if (isset($_COOKIE[$id.'_order'])) {
            $this->order = $_COOKIE[$id.'_order'];
         }
      }
   }   
 
   private function share_extension_shared($extensions, $document, $text) {
      /// añadimos las extensiones para proveedors, agentes y artículos      
      $extensions [] = array(
            'name' => $document.'_agente',
            'page_from' => __CLASS__,
            'page_to' => 'admin_agente',
            'type' => 'button',
            'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst($text),
            'params' => '');
      
      $extensions [] = array(
            'name' => $document.'_articulo',
            'page_from' => __CLASS__,
            'page_to' => 'ventas_articulo',
            'type' => 'tab_button',
            'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst($text),
            'params' => '');

      foreach ($extensions as $ext) {
         $fsext0 = new fs_extension($ext);
         if (!$fsext0->save()) {
            $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
         }
      }
   }
   
   private function delete_shared($model, $text) {
      $model0 = new $model();
      $record = $model0->get($_POST['delete']);
      if ($record) {
         if ($record->delete()) {
            $this->clean_last_changes();
         } else
            $this->new_error_msg("¡Imposible eliminar el " . $text . "!");
      } else
         $this->new_error_msg("¡" . ucfirst($text) . " no encontrado!");
   }
   
   private function buscar_shared($model, $table, $field, $where, $order2) {
      $this->resultados = array();
      $from = " FROM " .$table;
      $where = " WHERE 1 = 1 " .$where;
      
      if($this->query) {
         $query = $this->agente->no_html( mb_strtolower($this->query, 'UTF8') );
         if (is_numeric($query))
            $where .= " AND (codigo LIKE '%".$query."%' OR ".$field." LIKE '%".$query."%' OR observaciones LIKE '%".$query."%')";
         else
            $where .= " AND (lower(codigo) LIKE '%".$query."%'"
                       . " OR lower(".$field.") LIKE '%".$query."%'"
                       . " OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%')";
      }
      
      if($this->codagente)
         $where .= " AND codagente = ".$this->agente->var2str($this->codagente);
      
      if($this->codalmacen)
         $where .= " AND codalmacen = ".$this->agente->var2str($this->codalmacen);
            
      if($this->codserie)
         $where .= " AND codserie = ".$this->agente->var2str($this->codserie);
      
      if($this->desde)
         $where .= " AND fecha >= ".$this->agente->var2str($this->desde);
      
      if($this->hasta)
         $where .= " AND fecha <= ".$this->agente->var2str($this->hasta);
      
      $sql = "SELECT COUNT(*) as total" . $from . $where;
      $this->num_resultados = intval($this->db->select($sql)[0]["total"]);
      if($this->num_resultados > 0) {
         $sql = "SELECT *".$from .$where ." ORDER BY ".$this->order.$order2;
         $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $this->offset);
         if ($data) {
            foreach($data as $record) {
               $this->resultados[] = new $model($record);          
            }
            
            $sql = "SELECT coddivisa,SUM(total) as total" .$from .$where ." GROUP BY coddivisa";
            $data = $this->db->select($sql);
            if ($data) {
               $this->total_resultados_txt = 'Suma total de los resultados:';
            
               foreach($data as $record) {
                  $this->total_resultados[] = array(
                             'coddivisa' => $record['coddivisa'],
                             'total' => floatval($record['total'])
                           );
               }
            }
         }
      }
   }
   
   public function url_shared($busqueda = FALSE) {
      $url = parent::url();
      if ($busqueda) {
         $url .= "&mostrar=" . $this->mostrar
               . "&query=" . $this->query
               . "&codserie=" . $this->codserie
               . "&codagente=" . $this->codagente
               . "&codalmacen=" . $this->codalmacen
               . "&desde=" . $this->desde
               . "&hasta=" . $this->hasta;
      } 
      return $url;
   }
   
   public function paginas_shared() {
      switch ($this->mostrar) {
         case 'pendientes': {
            $total = $this->total_pendientes();
            break;
         }

         case 'buscar': {
            $total = $this->num_resultados;
            break;
         }

         case 'rechazados': {
            $total = $this->total_rechazados();
            break;
         }
         
         default: {
            $total = $this->total_registros();
            break;
         }
      }

      return $this->fbase_paginas($this->url(TRUE), $total, $this->offset);
   }
   
   public function orden_shared() {
      return [
          'fecha_desc' => [
              'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
              'texto' => 'Fecha',
              'orden' => 'fecha DESC'
          ],
          'fecha_asc' => [
              'icono' => '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>',
              'texto' => 'Fecha',
              'orden' => 'fecha ASC'
          ],
          'codigo_desc' => [
              'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
              'texto' => 'Código',
              'orden' => 'codigo DESC'
          ],
          'codigo_asc' => [
              'icono' => '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>',
              'texto' => 'Código',
              'orden' => 'codigo ASC'
          ],
          'total_desc' => [
              'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
              'texto' => 'Total',
              'orden' => 'total DESC'
          ]
      ];
   }
}
