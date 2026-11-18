<?php

// Modelo de la tabla facultad (columnas: id, nombre, abreviatura, estado)
// estado = 1 activa, estado = 0 eliminada de forma logica
class FacultadModel extends Model
{
    // REQ-01: solo facultades activas
    public function activas()
    {
        return $this->where("estado", 1)->orderBy("nombre")->get();
    }

    // Una facultad solo si esta activa; null si no existe o esta eliminada
    public function activa($id)
    {
        return $this->where(["id" => $id, "estado" => 1])->first();
    }

    // REQ-04: eliminacion logica (estado 1 -> 0)
    public function desactivar($id)
    {
        return $this->update($id, ["estado" => 0]);
    }
}
