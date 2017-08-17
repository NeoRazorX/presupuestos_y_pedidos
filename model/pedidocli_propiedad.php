<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * Propiedad de un pedido, para añadir propiedades fácilmente.
 * Orientado para guardar datos adicionales para la cabecera y pie del pedido.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class pedidocli_propiedad extends \fs_model
{
    /**
     * Nombre
     * @var string
     */
    public $name;
    
    /**
     * idpedido del pedido al que se asocia
     * @var int
     */
    public $idpedido;
    
    /**
     * Valor
     * @var string
     */
    public $text;
    
    public function __construct($a = FALSE)
    {
        parent::__construct('pedidocli_propiedades');
        if ($a) {
            $this->name = $a['name'];
            $this->idpedido = $a['idpedido'];
            $this->text = $a['text'];
        } else {
            $this->name = NULL;
            $this->idpedido = NULL;
            $this->text = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    public function exists()
    {
        if (is_null($this->name) OR is_null($this->idpedido)) {
            return FALSE;
        }

        $sql = "SELECT * FROM " . $this->table_name
                . " WHERE name = " . $this->var2str($this->name)
                . " AND idpedido = " . $this->var2str($this->idpedido) . ";";
        return $this->db->select($sql);
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name
                    . " SET text = " . $this->var2str($this->text)
                    . " WHERE name = " . $this->var2str($this->name)
                    . " AND idpedido = " . $this->var2str($this->idpedido) . ";";
        } else {
            $sql = "INSERT INTO pedido_propiedades (name, idpedido, text) VALUES ("
                    . $this->var2str($this->name)
                    . "," . $this->var2str($this->idpedido)
                    . "," . $this->var2str($this->text) . ");";
        }


        return $this->db->exec($sql);
    }

    public function delete()
    {
        $sql = "DELETE FROM " . $this->table_name
                . " WHERE name = " . $this->var2str($this->name)
                . " AND idpedido = " . $this->var2str($this->idpedido) . ";";
        return $this->db->exec($sql);
    }

    /**
     * Devuelve un array con los pares name => text para una idpedido dado.
     * @param string $idpedido
     * @return array
     */
    public function array_get($idpedido)
    {
        $vlist = array();

        $sql = "SELECT * FROM " . $this->table_name
                . " WHERE idpedido = " . $this->var2str($idpedido) . ";";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    public function array_save($idpedido, $values)
    {
        $done = TRUE;

        foreach ($values as $key => $value) {
            $aux = new \pedido_propiedad();
            $aux->name = $key;
            $aux->idpedido = $idpedido;
            $aux->text = $value;
            if (!$aux->save()) {
                $done = FALSE;
                break;
            }
        }

        return $done;
    }

}
