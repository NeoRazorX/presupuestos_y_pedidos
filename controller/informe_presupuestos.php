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
require_model('cliente.php');
require_model('proveedor.php');
require_model('forma_pago.php');


class informe_presupuestos extends fs_controller
{
   public $resultados;
   
   public $desde;
   public $hasta;
   public $estado;
   public $forma_pago;
   public $cliente;
   public $proveedor;
   public $agente;
   public $almacen;
   public $serie;
   public $divisa;
   

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS), 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      $this->desde = '';
      $this->hasta = '';
      $this->estado = '';
      $this->cliente = new cliente();
      $this->proveedor = new proveedor();
      $this->almacen = new almacen();
      $this->serie = new serie();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->agente = new agente();
      
      
      //recogemos valores
      if (isset($_REQUEST['desde']))
      {
         $this->desde = $_REQUEST['desde'];
      }
      if (isset($_REQUEST['hasta']))
      {
         $this->hasta = $_REQUEST['hasta'];
      }
      if (isset($_REQUEST['estado']))
      {
         $this->estado = $_REQUEST['estado'];
      }
      if (isset($_REQUEST['codcliente']) && $_REQUEST['codcliente'] != '')
      {
         $cli0 = new cliente();
         $this->cliente = $cli0->get($_REQUEST['codcliente']);
      }
      if (isset($_REQUEST['codproveedor']) && $_REQUEST['codproveedor'] != '')
      {
         $prov0 = new proveedor();
         $this->proveedor = $prov0->get($_REQUEST['codproveedor']);
      }
      if (isset($_REQUEST['codalmacen']) && $_REQUEST['codalmacen'] != '')
      {
         $alm0 = new almacen();
         $this->almacen = $alm0->get($_REQUEST['codalmacen']);
      }
      
      $codserie = FALSE;
      if(isset($_REQUEST['codserie']) && $_REQUEST['codserie'] != '')
      {
         $ser0 = new serie();
         $this->serie = $ser0->get($_REQUEST['codserie']);
         $codserie = FALSE;
         if ($this->serie)
         {
            $codserie = $this->serie->codserie;
         }
      }
      if (isset($_REQUEST['coddivisa']) && $_REQUEST['coddivisa'] != '')
      {
         $div0 = new divisa;
         $this->divisa = $div0->get($_REQUEST['coddivisa']);
      }
      if (isset($_REQUEST['codpago']) && $_REQUEST['codpago'] != '')
      {
         $fp0 = new forma_pago();
         $this->forma_pago = $fp0->get($_REQUEST['codpago']);
      }
      if (isset($_REQUEST['codagente']) && $_REQUEST['codagente'] != '')
      {
         $age0 = new agente();
         $this->agente = $age0->get($_REQUEST['codagente']);
      }
         
      
      
      //resultados
      $pre0 = new presupuesto_cliente();
      $this->resultados = $pre0->all_desde($this->desde, $this->hasta, $codserie);
      //print_r($this->resultados);
   }
   
   
}
