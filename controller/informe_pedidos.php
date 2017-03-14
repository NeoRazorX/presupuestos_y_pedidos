<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');

class informe_pedidos extends fs_controller
{
   public $prestashop;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $pedido_cli = new pedido_cliente();
      $pedido_pro = new pedido_proveedor();
      
      $this->prestashop = $this->db->table_exists('ps_orders');
   }
   
   public function stats_last_days()
   {
      $stats = array();
      $stats_cli = $this->stats_last_days_aux('pedidoscli');
      $stats_pro = $this->stats_last_days_aux('pedidosprov');
      
      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'day' => $value['day'],
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      if($this->prestashop)
      {
         $stats_ps = $this->stats_last_days_aux('ps_orders');
         foreach($stats_ps as $i => $value)
         {
            $stats[$i]['total_ps'] = round($value['total'], FS_NF0);
         }
      }
      
      return $stats;
   }
   
   private function stats_last_days_aux($table_name = 'pedidoscli', $numdays = 25)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$numdays.' day'));
      
      /// inicializamos los resultados
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 day', 'd') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('day' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMDD')";
      }
      else
         $sql_aux = "DATE_FORMAT(fecha, '%d')";
      
      if($table_name == 'ps_orders')
      {
         $sql = "SELECT ".$sql_aux." as dia, SUM(totaliva) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." GROUP BY ".$sql_aux." ORDER BY dia ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['dia']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
      }
      else
      {
         /// primero consultamos con la divisa de la empresa
         $sql = "SELECT ".$sql_aux." as dia, SUM(neto) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY dia ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['dia']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
         
         /// primero consultamos con la divisa de la empresa
         $sql = "SELECT ".$sql_aux." as dia, SUM(neto/tasaconv) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY dia ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['dia']);
               $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
            }
         }
      }
      
      return $stats;
   }
   
   public function stats_last_months()
   {
      $stats = array();
      $stats_cli = $this->stats_last_months_aux('pedidoscli');
      $stats_pro = $this->stats_last_months_aux('pedidosprov');
      $meses = array(
          1 => 'ene',
          2 => 'feb',
          3 => 'mar',
          4 => 'abr',
          5 => 'may',
          6 => 'jun',
          7 => 'jul',
          8 => 'ago',
          9 => 'sep',
          10 => 'oct',
          11 => 'nov',
          12 => 'dic'
      );
      
      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'month' => $meses[ $value['month'] ],
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      if($this->prestashop)
      {
         $stats_ps = $this->stats_last_months_aux('ps_orders');
         foreach($stats_ps as $i => $value)
         {
            $stats[$i]['total_ps'] = round($value['total'], FS_NF0);
         }
      }
      
      return $stats;
   }
   
   private function stats_last_months_aux($table_name = 'pedidoscli', $num = 11)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('1-m-Y').'-'.$num.' month'));
      
      /// inicializamos los resultados
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 month', 'm') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMMM')";
      }
      else
         $sql_aux = "DATE_FORMAT(fecha, '%m')";
      
      if($table_name == 'ps_orders')
      {
         $sql = "SELECT ".$sql_aux." as mes, SUM(totaliva) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." GROUP BY ".$sql_aux." ORDER BY mes ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['mes']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
      }
      else
      {
         /// primero consultamos la divisa de la empresa
         $sql = "SELECT ".$sql_aux." as mes, SUM(neto) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY mes ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['mes']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
         
         /// ahora consultamos el resto de divisas
         $sql = "SELECT ".$sql_aux." as mes, SUM(neto/tasaconv) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY mes ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['mes']);
               $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
            }
         }
      }
      
      return $stats;
   }
   
   public function stats_last_years()
   {
      $stats = array();
      $stats_cli = $this->stats_last_years_aux('pedidoscli');
      $stats_pro = $this->stats_last_years_aux('pedidosprov');
      
      foreach($stats_cli as $i => $value)
      {
         $stats[$i] = array(
             'year' => $value['year'],
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      if($this->prestashop)
      {
         $stats_ps = $this->stats_last_years_aux('ps_orders');
         foreach($stats_ps as $i => $value)
         {
            $stats[$i]['total_ps'] = round($value['total'], FS_NF0);
         }
      }
      
      return $stats;
   }
   
   private function stats_last_years_aux($table_name = 'pedidoscli', $num = 4)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$num.' year'));
      
      /// inicializamos los resultados
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 year', 'Y') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('year' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMYYYY')";
      }
      else
         $sql_aux = "DATE_FORMAT(fecha, '%Y')";
      
      if($table_name == 'ps_orders')
      {
         $sql = "SELECT ".$sql_aux." as ano, SUM(totaliva) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." GROUP BY ".$sql_aux." ORDER BY ano ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['ano']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
      }
      else
      {
         /// primero consultamos en la divisa de la empresa
         $sql = "SELECT ".$sql_aux." as ano, SUM(neto) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY ano ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['ano']);
               $stats[$i]['total'] = floatval($d['total']);
            }
         }
         
         /// ahora consultamos en el resto de divisas
         $sql = "SELECT ".$sql_aux." as ano, SUM(neto/tasaconv) as total FROM ".$table_name
                 ." WHERE fecha >= ".$this->empresa->var2str($desde)
                 ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
                 ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
                 ." GROUP BY ".$sql_aux." ORDER BY ano ASC;";
         $data = $this->db->select($sql);
         if($data)
         {
            foreach($data as $d)
            {
               $i = intval($d['ano']);
               $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
            }
         }
      }
      
      return $stats;
   }
   
   private function date_range($first, $last, $step = '+1 day', $format = 'd-m-Y' )
   {
      $dates = array();
      $current = strtotime($first);
      $last = strtotime($last);
      
      while( $current <= $last )
      {
         $dates[] = date($format, $current);
         $current = strtotime($step, $current);
      }
      
      return $dates;
   }
}
