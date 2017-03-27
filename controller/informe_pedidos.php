<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017  Itaca Software Libre  contacta@itacaswl.com
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
require_model('serie.php');
require_model('proveedor.php');
require_model('cliente.php');
require_model('almacen.php');
require_model('divisa.php');
require_model('forma_pago.php');

class informe_pedidos extends fs_controller
{

   public $pedidos_cli;
   public $pedidos_pro;
   public $desde;
   public $hasta;   
   public $agente;
   public $serie;
   public $almacen;   
   public $cliente;
   public $proveedor; 
   public $mostrar; 
   public $codserie;
   public $codagente;
	public $codproveedor;
	public $codcliente;
	public $codalmacen;	 
	public $where; 
	public $coddivisa;
	public $divisa; 
	public $forma_pago;
	public $codpago;	
      
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      /// declaramos los objetos sólo para asegurarnos de que existen las tablas
      $this->pedido_cli = new pedido_cliente();
      $this->pedido_pro = new pedido_proveedor();
      $this->agente = new agente();
		$this->divisa = new divisa();
		$this->almacen = new almacen();
		$this->forma_pago = new forma_pago();		
      $this->desde = Date('1-1-Y');
      $this->hasta = Date('t-m-Y');      
      $this->serie = new serie();
      
      $this->mostrar = 'general';
      if( isset($_REQUEST['mostrar']) )
      {
         $this->mostrar = $_REQUEST['mostrar'];
      }      
      if( isset($_REQUEST['desde']) )
      {
         $this->desde = $_REQUEST['desde'];
      }         
      if( isset($_REQUEST['hasta']) )
      {
         $this->hasta = $_REQUEST['hasta'];
      }  

      if(!empty($_REQUEST['codserie']))
      {
         $this->codserie = $_REQUEST['codserie'];
      }  
      else 		
      	$this->codserie=FALSE;		

      if(!empty($_REQUEST['codpago']))
      {
         $this->codpago = $_REQUEST['codpago'];
      }  
      else 		
      	$this->codpago=FALSE;			

      if(!empty($_REQUEST['codagente']) )
      {
         $this->codagente = $_REQUEST['codagente'];
      }
      else
			$this->codagente=FALSE;              
          
      if(!empty($_REQUEST['codalmacen']) )
      {
         $this->codalmacen = $_REQUEST['codalmacen'];
      }
      else
			$this->codalmacen=FALSE; 


      if(!empty($_REQUEST['coddivisa']) )
      {
         $this->coddivisa = $_REQUEST['coddivisa'];
      }
      else
			$this->coddivisa=FALSE; 

   }


   public function stats_months()
   {
      $stats = array();
      $stats_cli = $this->stats_months_aux('pedidoscli');
      $stats_pro = $this->stats_months_aux('pedidosprov');
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
      	
      	$mesletra="";
      	$ano="";
      	
      	if (!empty($value['month']))
      	{
	      	$mesletra=$meses[intval(substr((string)$value['month'],0,strlen((string)$value['month'])-2))];
	      	$ano=substr((string)$value['month'],-2);
      	}
	
      	
         $stats[$i] = array(
 //            'month' => $meses[ $value['month'] ],
           // 'month' => $value['month'] ,    
           	 'month' => $mesletra.$ano , 
             'total_cli' => round($value['total'], FS_NF0),
             'total_pro' => 0
         );
      }
      
      foreach($stats_pro as $i => $value)
      {
         $stats[$i]['total_pro'] = round($value['total'], FS_NF0);
      }
      
      
      return $stats;
   }
   
   private function stats_months_aux($table_name = 'pedidoscli', $num = 0)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime($this->desde.'-'.$num.' month'));
      
      /// inicializamos los resultados
      foreach($this->date_range($desde, $this->hasta, '+1 month', 'my') as $date)
      {
        $i = intval($date);
        $stats[$i] = array('month' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
      {
         $sql_aux = "to_char(fecha,'FMMMYY')";

      }
      else
      {
         $sql_aux = "DATE_FORMAT(fecha, '%m%y')";

      }


      /// primero consultamos la divisa de la empresa
      $sql = "SELECT ".$sql_aux." as mes, SUM(neto) as total FROM ".$table_name;
      
      
      $this->where="";
      $this->where.= " WHERE fecha >= ".$this->empresa->var2str($desde)
              ." AND fecha <= ".$this->empresa->var2str($this->hasta);

              
		if ($this->codserie)
			$this->where.=" AND codserie = ".$this->empresa->var2str($this->codserie);	    

		if ($this->codagente)
			$this->where.=" AND codagente = ".$this->empresa->var2str($this->codagente);  

		if ($this->codalmacen)
			$this->where.=" AND coddivisa = ".$this->empresa->var2str($this->coddivisa);
			

		if ($this->coddivisa) {
		
			$this->where.=" AND coddivisa = ".$this->empresa->var2str($this->coddivisa);		
		
		}
		else 
		{
			$this->where.=" AND coddivisa = ".$this->empresa->var2str($this->empresa->coddivisa);		
		}			 
		
		
		
					
		$sql.=$this->where;
					              
      $sql.=" GROUP BY ".$sql_aux." ORDER BY mes ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i]['total'] = floatval($d['total']);
         }
      }
      
      
      
      /// ahora consultamos el resto de divisas si la divisa no es una concreta y después la convertimos a euros

		if (!$this->coddivisa) {
      
	      $sql = "SELECT ".$sql_aux." as mes, SUM(neto/tasaconv) as total FROM ".$table_name
	              ." WHERE fecha >= ".$this->empresa->var2str($desde)
	              ." AND fecha <= ".$this->empresa->var2str($this->hasta)
	              ." AND coddivisa != ".$this->empresa->var2str($this->empresa->coddivisa);
	
			if ($this->codserie)
				$sql.=" AND codserie = ".$this->empresa->var2str($this->codserie);	    
	
			if ($this->codagente)
				$sql.=" AND codagente = ".$this->empresa->var2str($this->codagente);              
	
			if ($this->codalmacen)
				$sql.=" AND codalmacen = ".$this->empresa->var2str($this->codalmacen);                
	              
	      $sql.=" GROUP BY ".$sql_aux." ORDER BY mes ASC;";
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

   public function stats_series($tabla = 'pedidosprov')
   {
      $stats = array();
      $serie0 = new serie();
      
      $sql = "select codserie,sum(neto) as total from ".$tabla;
		$sql .=$this->where;      
      $sql .=" group by codserie order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $serie = $serie0->get($d['codserie']);
            if($serie)
            {
               $stats[] = array(
                   'txt' => $serie->descripcion,
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codserie'],
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }    


   public function stats_agentes($tabla = 'pedidosprov')
   {
      $stats = array();
      $agente0 = new agente();
      
      $sql = "select codagente,sum(neto) as total from ".$tabla;
		$sql .=$this->where;      
      $sql .=" group by codagente order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $agente = $agente0->get($d['codagente']);
            if($agente)
            {
               $stats[] = array(
                   'txt' => $agente->get_fullname(),
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codagente'],
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }
   
   
   public function stats_almacenes($tabla = 'pedidosprov')
   {
      $stats = array();
      $al0 = new almacen();
      
      $sql = "select codalmacen,sum(neto) as total from ".$tabla;
		$sql .=$this->where;     
		$sql .=" group by codalmacen order by total desc;"; 
		      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $alma = $al0->get($d['codalmacen']);
            if($alma)
            {
               $stats[] = array(
                   'txt' => $alma->nombre,
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codalmacen'],
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }      


   public function stats_formas_pago($tabla = 'pedidosprov')
   {
      $stats = array();
      $fp0 = new forma_pago();
      
      $sql = "select codpago,sum(neto) as total from ".$tabla;
		$sql .=$this->where;            
      $sql .=" group by codpago order by total desc;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $formap = $fp0->get($d['codpago']);
            if($formap)
            {
               $stats[] = array(
                   'txt' => $formap->descripcion,
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
            else
            {
               $stats[] = array(
                   'txt' => $d['codpago'],
                   'total' => round( abs( $this->euro_convert( floatval($d['total']) ) ), FS_NF0)
               );
            }
         }
      }
      
      return $stats;
   }

}
