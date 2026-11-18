<?php

// Gestion de facultades (REQ-01 a REQ-04)
// Rutas: /facultad, /facultad/registrar, /facultad/editar/1 (guardar, actualizar y eliminar reciben POST)
class FacultadController extends Controller
{
    // REQ-01: lista solo las facultades activas
    public function index()
    {
        $this->views->render($this, "listar", [
            "titulo" => "Facultades",
            "filas" => $this->model->activas(),
        ]);
    }

    // Formulario vacio para registrar
    public function registrar()
    {
        $this->views->render($this, "formulario", [
            "titulo" => "Nueva facultad",
            "id" => null,
            "old" => [],
            "errores" => [],
        ]);
    }

    // REQ-02: recibe el formulario de registro
    public function guardar()
    {
        $this->soloPost();
        $datos = $this->datosDelFormulario();
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->views->render($this, "formulario", [
                "titulo" => "Nueva facultad",
                "id" => null,
                "old" => $_POST,
                "errores" => $errores,
            ]);
        }

        $datos["estado"] = 1;
        $this->model->insert($datos);
        $this->redirect("facultad");
    }

    // Formulario con los datos actuales
    public function editar($id)
    {
        $fila = $this->model->activa($id);
        if (!$fila) {
            return $this->notFound();
        }
        $this->views->render($this, "formulario", [
            "titulo" => "Editar facultad",
            "id" => $id,
            "old" => $fila,
            "errores" => [],
        ]);
    }

    // REQ-03: recibe la edicion (solo nombre y abreviatura)
    public function actualizar($id)
    {
        $this->soloPost();
        if (!$this->model->activa($id)) {
            return $this->notFound();
        }

        $datos = $this->datosDelFormulario();
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->views->render($this, "formulario", [
                "titulo" => "Editar facultad",
                "id" => $id,
                "old" => $_POST,
                "errores" => $errores,
            ]);
        }

        $this->model->update($id, $datos);
        $this->redirect("facultad");
    }

    // REQ-04: eliminacion logica (estado 1 -> 0), solo por POST
    public function eliminar($id)
    {
        $this->soloPost();
        if ($this->model->activa($id)) {
            $this->model->desactivar($id);
        }
        $this->redirect("facultad");
    }

    // Las acciones que cambian datos no se ejecutan abriendo la URL: si no es POST vuelven al listado
    private function soloPost()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("facultad");
        }
    }

    // Toma del formulario solo los campos editables de la facultad
    private function datosDelFormulario()
    {
        return [
            "nombre" => trim($_POST["nombre"] ?? ""),
            "abreviatura" => trim($_POST["abreviatura"] ?? ""),
        ];
    }

    // Devuelve la lista de errores; vacia si los datos son validos
    private function validar($datos)
    {
        $errores = [];
        if ($datos["nombre"] === "") {
            $errores[] = "El nombre es obligatorio.";
        } elseif (mb_strlen($datos["nombre"]) > 120) {
            $errores[] = "El nombre no puede superar 120 caracteres.";
        }
        if ($datos["abreviatura"] === "") {
            $errores[] = "La abreviatura es obligatoria.";
        } elseif (mb_strlen($datos["abreviatura"]) > 20) {
            $errores[] = "La abreviatura no puede superar 20 caracteres.";
        }
        return $errores;
    }
}
