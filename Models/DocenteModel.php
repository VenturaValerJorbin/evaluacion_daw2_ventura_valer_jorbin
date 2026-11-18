<?php

// Modelo de la tabla docente (columnas: id, id_facultad, dni, apellidos, nombres, correo, categoria, dedicacion, estado)
// Cada docente pertenece a una facultad (docente.id_facultad -> facultad.id)
class DocenteModel extends Model
{
    // Valores permitidos de las columnas ENUM (REQ-09 y REQ-10)
    const CATEGORIAS = ["AUXILIAR", "ASOCIADO", "PRINCIPAL"];
    const DEDICACIONES = ["DEDICACION EXCLUSIVA-DE", "TIEMPO COMPLETO-TC", "TIEMPO PARCIAL-TP"];

    // REQ-05 y REQ-06: docentes activos con el nombre de su facultad
    public function listado()
    {
        return $this->select("docente.*, f.nombre AS facultad, f.abreviatura AS facultad_abreviatura")
            ->join("facultad f", "f.id = docente.id_facultad")
            ->where("docente.estado", 1)
            ->orderBy("docente.apellidos")
            ->orderBy("docente.nombres")
            ->get();
    }

    // Un docente solo si esta activo; null si no existe o esta eliminado
    public function activo($id)
    {
        return $this->where(["id" => $id, "estado" => 1])->first();
    }

    // Docente activo con ese DNI (para evitar duplicados); null si no hay
    public function porDni($dni)
    {
        return $this->where(["dni" => $dni, "estado" => 1])->first();
    }

    // REQ-08: facultades activas para la lista desplegable del formulario
    public function facultades()
    {
        return (new Model("facultad"))->where("estado", 1)->orderBy("nombre")->get();
    }

    // Facultad activa por id (valida que la facultad elegida exista)
    public function facultadActiva($id)
    {
        return (new Model("facultad"))->where(["id" => $id, "estado" => 1])->first();
    }

    // REQ-12: eliminacion logica (estado 1 -> 0)
    public function desactivar($id)
    {
        return $this->update($id, ["estado" => 0]);
    }
}
