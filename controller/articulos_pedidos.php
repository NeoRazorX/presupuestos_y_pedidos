<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of articulos_compras
 *
 * @author Luismipr <luismipr@gmail.com>
 */

require_model('articulos.php');
require_model('pedido_proveedor.php');
require_model('pedido_cliente.php');

class articulos_pedidos extends fs_controller
{
   public $resultados;
   public $pedidoprov;
   public $pedidocli;
   
   public function __construct()
   {
       parent::__construct(__CLASS__, 'Artículos Pedidos', 'compras', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      //cargamos extensiones
      $this->share_extensions();
      $this->pedidoprov = new pedido_proveedor();
      $this->pedidocli = new pedido_cliente();

      //mostramos resultados
      $this->resultados = $this->buscar_articulos();
      
   }
   
   public function share_extensions()
   {
      //botón articulos pendientes de recibir en copras_pedidos
      $fsext = new fs_extension();
      $fsext->name = 'button_articulos_pedidos_compras';
      $fsext->from = __CLASS__;
      $fsext->to = 'compras_pedidos';
      $fsext->type = 'button';
      $fsext->text = '<span class="glyphicon glyphicon-import" aria-hidden="true"></span> &nbsp; Artículos pedidos';
      $fsext->save();
      
      //botón articulos pendientes de recibir en ventas_pedidos
      $fsext1 = new fs_extension();
      $fsext1->name = 'button_articulos_pedidos_ventas';
      $fsext1->from = __CLASS__;
      $fsext1->to = 'ventas_pedidos';
      $fsext1->type = 'button';
      $fsext1->text = '<span class="glyphicon glyphicon-import" aria-hidden="true"></span> &nbsp; Artículos pedidos';
      $fsext1->save();
      
      //botón articulos pendientes de recibir en ventas_articulos
      $fsext2 = new fs_extension();
      $fsext2->name = 'button_articulos_pedidos_articulos';
      $fsext2->from = __CLASS__;
      $fsext2->to = 'ventas_articulos';
      $fsext2->type = 'button';
      $fsext2->text = '<span class="glyphicon glyphicon-import" aria-hidden="true"></span> &nbsp; Artículos pedidos';
      $fsext2->save();
   }
   
   
   public function buscar_articulos()
   {
      $artlist = array();
      $art0 = new articulo();

      $sql = "SELECT sum(cantidad) as cantidadcompras,referencia,lineaspedidosprov.idpedido "
              . "FROM lineaspedidosprov INNER JOIN pedidosprov ON lineaspedidosprov.idpedido = pedidosprov.idpedido "
              . "WHERE pedidosprov.idalbaran IS NULL AND lineaspedidosprov.referencia IS NOT NULL "
              . "GROUP BY referencia,lineaspedidosprov.idpedido ORDER BY referencia;";
      $data = $this->db->select($sql);
      if ($data)
      {
         foreach ($data as $d)
         {
            $articulo = $art0->get($d['referencia']);
            if ($articulo)
            {
               if (isset($artlist[$articulo->referencia]))
               {
                  $artlist[$articulo->referencia]['cantidadcompras'] += floatval($d['cantidadcompras']);
               }
               else
               {
                  $artlist[$articulo->referencia] = array(
                      'referencia' => $d['referencia'],
                      'cantidadcompras' => floatval($d['cantidadcompras']),
                      'cantidadventas' => 0,
                      'descripcion' => $articulo->descripcion,
                      'stockfisico' => $articulo->stockfis,
                      'pedidoscompras' => array()
                  );
               }
               $pedido = $this->pedidoprov->get($d['idpedido']);
               if ($pedido)
               {
                  if ($pedido->idpedido == $d['idpedido'])
                  {
                     $pedido->cantidadpedido = $d['cantidadcompras'];
                  }
                  $artlist[$articulo->referencia]['pedidoscompras'][] = $pedido;
               }
            }
         }
      }

      //ventas
      $sql1= "SELECT sum(cantidad) as cantidadventas,referencia,lineaspedidoscli.idpedido "
              . "FROM lineaspedidoscli INNER JOIN pedidoscli ON lineaspedidoscli.idpedido = pedidoscli.idpedido "
              . "WHERE pedidoscli.idalbaran IS NULL AND status = '0' AND lineaspedidoscli.referencia IS NOT NULL "
              . "GROUP BY referencia,lineaspedidoscli.idpedido ORDER BY referencia;";
      $data1 = $this->db->select($sql1);
      if ($data1)
      {
         foreach ($data1 as $d1)
         {
            $articulo = $art0->get($d1['referencia']);
            if ($articulo)
            {
               if (isset($artlist[$articulo->referencia]))
               {
                  $artlist[$articulo->referencia]['cantidadventas'] += floatval($d1['cantidadventas']);
               }
               else
               {
                  $artlist[$articulo->referencia] = array(
                      'referencia' => $d1['referencia'],
                      'cantidadcompras' => 0,
                      'cantidadventas' => floatval($d1['cantidadventas']),
                      'descripcion' => $articulo->descripcion,
                      'pedidosventas' => array()
                  );
               }
               $pedido1 = $this->pedidocli->get($d1['idpedido']);
               if ($pedido1)
               {
                  if ($pedido1->idpedido == $d1['idpedido'])
                  {
                     $pedido1->cantidadpedido = $d1['cantidadventas'];
                  }
                  $artlist[$articulo->referencia]['pedidosventas'][] = $pedido1;
               }
            }
         }
      }
    return $artlist;
   }

}
