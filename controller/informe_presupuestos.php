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

require_model('presupuesto_cliente.php');
require_model('pedido_cliente.php');

class informe_presupuestos extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS), 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $presupuesto = new presupuesto_cliente();
      $pedido = new pedido_cliente();
   }
   
   public function stats_last_days()
   {
      $stats = array();
      
      $stats_pre = $this->stats_last_days_aux('presupuestoscli');
      foreach($stats_pre as $i => $value)
      {
         $stats[$i] = array(
             'day' => $value['day'],
             'total_pre' => round($value['total'], FS_NF0),
             'total_ped' => 0
         );
      }
      
      $stats_ped = $this->stats_last_days_aux('pedidoscli');
      foreach($stats_ped as $i => $value)
      {
         $stats[$i]['total_ped'] = round($value['total'], FS_NF0);
      }
      
      return $stats;
   }
   
   private function stats_last_days_aux($table_name = 'presupuestoscli', $numdays = 25)
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
      
      /// primero consultamos la divisa de la empresa
      $data = $this->db->select("SELECT ".$sql_aux." as dia, sum(neto) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY dia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['dia']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      /// ahora consultamos las demás divisas
      $data = $this->db->select("SELECT ".$sql_aux." as dia, sum(neto/tasaconv) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY dia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['dia']);
            $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
         }
      }
      
      return $stats;
   }
   
   public function stats_last_months()
   {
      $stats = array();
      $stats_pre = $this->stats_last_months_aux('presupuestoscli');
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
      
      foreach($stats_pre as $i => $value)
      {
         $stats[$i] = array(
             'month' => $meses[ $value['month'] ],
             'total_pre' => round($value['total'], FS_NF0),
             'total_ped' => 0
         );
      }
      
      $stats_ped = $this->stats_last_months_aux('pedidoscli');
      foreach($stats_ped as $i => $value)
      {
         $stats[$i]['total_ped'] = round($value['total'], FS_NF0);
      }
      
      return $stats;
   }
   
   private function stats_last_months_aux($table_name = 'presupuestoscli', $num = 11)
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
      
      /// primero consultamos con la divisa de la empresa
      $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(neto) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY mes ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      /// ahora consultamos el resto de divisas
      $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(neto/tasaconv) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY mes ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
         }
      }
      
      return $stats;
   }
   
   public function stats_last_years()
   {
      $stats = array();
      
      $stats_pre = $this->stats_last_years_aux('presupuestoscli');
      foreach($stats_pre as $i => $value)
      {
         $stats[$i] = array(
             'year' => $value['year'],
             'total_pre' => round($value['total'], FS_NF0),
             'total_ped' => 0
         );
      }
      
      $stats_ped = $this->stats_last_years_aux('pedidoscli');
      foreach($stats_ped as $i => $value)
      {
         $stats[$i]['total_ped'] = round($value['total'], FS_NF0);
      }
      
      return $stats;
   }
   
   private function stats_last_years_aux($table_name = 'presupuestoscli', $num = 4)
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
      
      /// primero consultamos con la divisa de la empresa
      $data = $this->db->select("SELECT ".$sql_aux." as ano, sum(neto) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY ano ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      /// ahora consultamos el resto de divisas
      $data = $this->db->select("SELECT ".$sql_aux." as ano, sum(neto/tasaconv) as total FROM ".$table_name
              ." WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str(Date('d-m-Y'))
              ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa)
              ." GROUP BY ".$sql_aux." ORDER BY ano ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i]['total'] += $this->euro_convert( floatval($d['total']) );
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
