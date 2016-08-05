<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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

namespace FacturaScripts\model;

require_model('pedido_proveedor.php');

/**
 * Línea de pedido de proveedor.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class linea_pedido_proveedor extends \fs_model
{
   /**
    * Clave primaria.
    * @var type 
    */
   public $idlinea;
   
   /**
    * ID del pedido.
    * @var type 
    */
   public $idpedido;
   
   public $cantidad;
   public $codimpuesto;
   public $descripcion;
   public $dtopor;
   public $irpf;
   public $iva;
   public $pvpsindto;
   public $pvptotal;
   public $pvpunitario;
   public $recargo;
   public $referencia;
   
   private static $pedidos;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspedidosprov');
      
      if( !isset(self::$pedidos) )
      {
         self::$pedidos = array();
      }
      
      if($l)
      {
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtopor = floatval($l['dtopor']);
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idpedido = $this->intval($l['idpedido']);
         $this->irpf = floatval($l['irpf']);
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->recargo = floatval($l['recargo']);
         $this->referencia = $l['referencia'];
      }
      else
      {
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->dtopor = 0;
         $this->idlinea = NULL;
         $this->idpedido = NULL;
         $this->irpf = 0;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->recargo = 0;
         $this->referencia = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function pvp_iva()
   {
      return $this->pvpunitario*(100+$this->iva)/100;
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva-$this->irpf+$this->recargo)/100;
   }
   
   public function show_codigo()
   {
      $codigo = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $codigo = $p->codigo;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \pedido_proveedor();
         self::$pedidos[] = $pre->get($this->idpedido);
         $codigo = self::$pedidos[ count(self::$pedidos)-1 ]->codigo;
      }
      
      return $codigo;
   }
   
   public function show_fecha()
   {
      $fecha = 'desconocida';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $fecha = $p->fecha;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \pedido_proveedor();
         self::$pedidos[] = $pre->get($this->idpedido);
         $fecha = self::$pedidos[ count(self::$pedidos)-1 ]->fecha;
      }
      
      return $fecha;
   }
   
   public function show_nombre()
   {
      $nombre = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $nombre = $p->nombre;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \pedido_proveedor();
         self::$pedidos[] = $pre->get($this->idpedido);
         $nombre = self::$pedidos[ count(self::$pedidos)-1 ]->nombre;
      }
      
      return $nombre;
   }
   
   public function url()
   {
      return 'index.php?page=compras_pedido&id='.$this->idpedido;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) OR $this->referencia == '')
      {
         return "index.php?page=ventas_articulos";
      }
      else
         return "index.php?page=ventas_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( !$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia." del ".FS_PEDIDO.". Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia." del ".FS_PEDIDO.". Valor correcto: ".$totalsindto);
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->cantidad)
                    .", codimpuesto = ".$this->var2str($this->codimpuesto)
                    .", descripcion = ".$this->var2str($this->descripcion)
                    .", dtopor = ".$this->var2str($this->dtopor)
                    .", idpedido = ".$this->var2str($this->idpedido)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", iva = ".$this->var2str($this->iva)
                    .", pvpsindto = ".$this->var2str($this->pvpsindto)
                    .", pvptotal = ".$this->var2str($this->pvptotal)
                    .", pvpunitario = ".$this->var2str($this->pvpunitario)
                    .", recargo = ".$this->var2str($this->recargo)
                    .", referencia = ".$this->var2str($this->referencia)
                    ."  WHERE idlinea = ".$this->var2str($this->idlinea).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtopor,
               idpedido,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia)
               VALUES (".$this->var2str($this->cantidad)
                    .",".$this->var2str($this->codimpuesto)
                    .",".$this->var2str($this->descripcion)
                    .",".$this->var2str($this->dtopor)
                    .",".$this->var2str($this->idpedido)
                    .",".$this->var2str($this->irpf)
                    .",".$this->var2str($this->iva)
                    .",".$this->var2str($this->pvpsindto)
                    .",".$this->var2str($this->pvptotal)
                    .",".$this->var2str($this->pvpunitario)
                    .",".$this->var2str($this->recargo)
                    .",".$this->var2str($this->referencia).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   /**
    * Devuelve todas las líneas del pedido $idp
    * @param type $idp
    * @return \linea_pedido_proveedor
    */
   public function all_from_pedido($idp)
   {
      $plist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($idp)
              ." ORDER BY idlinea ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $plist[] = new \linea_pedido_proveedor($d);
         }
      }
      
      return $plist;
   }
   
   /**
    * Devuelve todas las líneas que hagan referencia al artículo $ref
    * @param type $ref
    * @param type $offset
    * @param type $limit
    * @return \linea_pedido_proveedor
    */
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref)
              ." ORDER BY idpedido DESC";
      
      $data = $this->db->select_limit($sql, $limit, $offset);
      if($data)
      {
         foreach($data as $l)
         {
            $linealist[] = new \linea_pedido_proveedor($l);
         }
      }
      
      return $linealist;
   }
   
   /**
    * Busca todas las coincidencias de $query en las líneas.
    * @param type $query
    * @param type $offset
    * @return \linea_pedido_proveedor
    */
   public function search($query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $sql .= "referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%'";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%'";
      }
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $l)
         {
            $linealist[] = new \linea_pedido_proveedor($l);
         }
      }
      
      return $linealist;
   }
}
