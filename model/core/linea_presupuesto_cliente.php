<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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

require_model('presupuesto_cliente.php');

/**
 * Línea de presupuesto de cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class linea_presupuesto_cliente extends \fs_model
{
   /**
    * Clave primaria.
    * @var type 
    */
   public $idlinea;
   
   /**
    * ID del presupuesto.
    * @var type 
    */
   public $idpresupuesto;
   
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
   
   public $orden;
   public $mostrar_cantidad;
   public $mostrar_precio;
   
   private static $presupuestos;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspresupuestoscli');
      
      if( !isset(self::$presupuestos) )
      {
         self::$presupuestos = array();
      }
      
      if($l)
      {
         $this->idlinea = intval($l['idlinea']);
         $this->idpresupuesto = intval($l['idpresupuesto']);
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtopor = floatval($l['dtopor']);
         $this->irpf = floatval($l['irpf']);
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->recargo = floatval($l['recargo']);
         $this->referencia = $l['referencia'];
         
         $this->orden = intval($l['orden']);
         $this->mostrar_cantidad = $this->str2bool($l['mostrar_cantidad']);
         $this->mostrar_precio = $this->str2bool($l['mostrar_precio']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idpresupuesto = NULL;
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->dtopor = 0;
         $this->irpf = 0;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->recargo = 0;
         $this->referencia = NULL;
         
         $this->orden = 0;
         $this->mostrar_cantidad = TRUE;
         $this->mostrar_precio = TRUE;
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
   
   public function descripcion()
   {
      return nl2br($this->descripcion);
   }
   
   public function show_codigo()
   {
      $codigo = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $codigo = $p->codigo;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \presupuesto_cliente();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $codigo = self::$presupuestos[ count(self::$presupuestos)-1 ]->codigo;
      }
      
      return $codigo;
   }
   
   public function show_fecha()
   {
      $fecha = 'desconocida';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $fecha = $p->fecha;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \presupuesto_cliente();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $fecha = self::$presupuestos[ count(self::$presupuestos)-1 ]->fecha;
      }
      
      return $fecha;
   }
   
   public function show_nombrecliente()
   {
      $nombre = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $nombre = $p->nombrecliente;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new \presupuesto_cliente();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $nombre = self::$presupuestos[ count(self::$presupuestos)-1 ]->nombrecliente;
      }
      
      return $nombre;
   }
   
   public function url()
   {
      return 'index.php?page=ventas_presupuesto&id='.$this->idpresupuesto;
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
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia." del ".FS_PRESUPUESTO.". Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia." del ".FS_PRESUPUESTO.". Valor correcto: ".$totalsindto);
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
                    .", idpresupuesto = ".$this->var2str($this->idpresupuesto)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", iva = ".$this->var2str($this->iva)
                    .", pvpsindto = ".$this->var2str($this->pvpsindto)
                    .", pvptotal = ".$this->var2str($this->pvptotal)
                    .", pvpunitario = ".$this->var2str($this->pvpunitario)
                    .", recargo = ".$this->var2str($this->recargo)
                    .", referencia = ".$this->var2str($this->referencia)
                    .", orden = ".$this->var2str($this->orden)
                    .", mostrar_cantidad = ".$this->var2str($this->mostrar_cantidad)
                    .", mostrar_precio = ".$this->var2str($this->mostrar_precio)
                    ."  WHERE idlinea = ".$this->var2str($this->idlinea).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtopor,
               idpresupuesto,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia,orden,
               mostrar_cantidad,mostrar_precio) VALUES (".$this->var2str($this->cantidad)
                    .",".$this->var2str($this->codimpuesto)
                    .",".$this->var2str($this->descripcion)
                    .",".$this->var2str($this->dtopor)
                    .",".$this->var2str($this->idpresupuesto)
                    .",".$this->var2str($this->irpf)
                    .",".$this->var2str($this->iva)
                    .",".$this->var2str($this->pvpsindto)
                    .",".$this->var2str($this->pvptotal)
                    .",".$this->var2str($this->pvpunitario)
                    .",".$this->var2str($this->recargo)
                    .",".$this->var2str($this->referencia)
                    .",".$this->var2str($this->orden)
                    .",".$this->var2str($this->mostrar_cantidad)
                    .",".$this->var2str($this->mostrar_precio).");";
            
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
    * Devuelve las líneas del presupuesto $idp
    * @param type $idp
    * @return \linea_presupuesto_cliente
    */
   public function all_from_presupuesto($idp)
   {
      $plist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($idp)
              ." ORDER BY orden DESC, idlinea ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $plist[] = new \linea_presupuesto_cliente($d);
         }
      }
      
      return $plist;
   }
   
   /**
    * Devuelve todas las líneas que hagan referencia al artículo $ref
    * @param type $ref
    * @param type $offset
    * @param type $limit
    * @return \linea_presupuesto_cliente
    */
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref)
              ." ORDER BY idpresupuesto DESC";
      
      $data = $this->db->select_limit($sql, $limit, $offset);
      if($data)
      {
         foreach($data as $l)
         {
            $linealist[] = new \linea_presupuesto_cliente($l);
         }
      }
      
      return $linealist;
   }
   
   /**
    * Busca todas las coincidencias de $query en las líneas.
    * @param type $query
    * @param type $offset
    * @return \linea_presupuesto_cliente
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
      $sql .= " ORDER BY idpresupuesto DESC, idlinea ASC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $l)
         {
            $linealist[] = new \linea_presupuesto_cliente($l);
         }
      }
      
      return $linealist;
   }
   
   /**
    * Busca todas las coincidencias de $query en las líneas del cliente $codcliente
    * @param type $codcliente
    * @param type $ref
    * @param type $obs
    * @param type $offset
    * @return \linea_presupuesto_cliente
    */
   public function search_from_cliente2($codcliente, $ref='', $obs='', $offset=0)
   {
      $linealist = array();
      $ref = mb_strtolower( $this->no_html($ref), 'UTF8' );
      $obs = mb_strtolower( $this->no_html($obs), 'UTF8' );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpresupuesto IN
         (SELECT idpresupuesto FROM presupuestoscli WHERE codcliente = ".$this->var2str($codcliente)."
         AND lower(observaciones) LIKE '".$obs."%') AND ";
      if( is_numeric($ref) )
      {
         $sql .= "(referencia LIKE '%".$ref."%' OR descripcion LIKE '%".$ref."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $ref);
         $sql .= "(lower(referencia) LIKE '%".$ref."%' OR lower(descripcion) LIKE '%".$ref."%')";
      }
      $sql .= " ORDER BY idpresupuesto DESC, idlinea ASC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $l)
         {
            $linealist[] = new \linea_presupuesto_cliente($l);
         }
      }
      
      return $linealist;
   }
}
