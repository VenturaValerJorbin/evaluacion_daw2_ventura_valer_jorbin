<?php

// Gestion de docentes (REQ-05 a REQ-12)
// Rutas: /docente, /docente/registrar, /docente/editar/1 (guardar, actualizar y eliminar reciben POST)
class DocenteController extends Controller
{
    // REQ-05 y REQ-06: lista solo los docentes activos, con su facultad
    public function index()
    {
        $this->views->render($this, "listar", [
            "titulo" => "Docentes",
            "filas" => $this->model->listado(),
        ]);
    }

    // Formulario vacio para registrar
    public function registrar()
    {
        $this->mostrarFormulario("Nuevo docente", null, [], []);
    }

    // REQ-07: recibe el formulario de registro
    public function guardar()
    {
        $this->soloPost();
        $datos = $this->datosDelFormulario();
        $errores = $this->validar($datos, null);

        if ($errores) {
            return $this->mostrarFormulario("Nuevo docente", null, $_POST, $errores);
        }

        $datos["estado"] = 1;
        $this->model->insert($datos);
        $this->redirect("docente");
    }

    // Formulario con los datos actuales
    public function editar($id)
    {
        $fila = $this->model->activo($id);
        if (!$fila) {
            return $this->notFound();
        }
        $this->mostrarFormulario("Editar docente", $id, $fila, []);
    }

    // REQ-11: recibe la edicion
    public function actualizar($id)
    {
        $this->soloPost();
        if (!$this->model->activo($id)) {
            return $this->notFound();
        }

        $datos = $this->datosDelFormulario();
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->mostrarFormulario("Editar docente", $id, $_POST, $errores);
        }

        $this->model->update($id, $datos);
        $this->redirect("docente");
    }

    // REQ-12: eliminacion logica (estado 1 -> 0), solo por POST
    public function eliminar($id)
    {
        $this->soloPost();
        if ($this->model->activo($id)) {
            $this->model->desactivar($id);
        }
        $this->redirect("docente");
    }

    // Muestra el formulario (registro o edicion) con las facultades para la lista desplegable (REQ-08)
    private function mostrarFormulario($titulo, $id, $old, $errores)
    {
        $this->views->render($this, "formulario", [
            "titulo" => $titulo,
            "id" => $id,
            "old" => $old,
            "errores" => $errores,
            "facultades" => $this->model->facultades(),
            "categorias" => DocenteModel::CATEGORIAS,
            "dedicaciones" => DocenteModel::DEDICACIONES,
        ]);
    }

    // Las acciones que cambian datos no se ejecutan abriendo la URL: si no es POST vuelven al listado
    private function soloPost()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("docente");
        }
    }

    // Toma del formulario solo los campos editables del docente
    private function datosDelFormulario()
    {
        return [
            "id_facultad" => (int)($_POST["id_facultad"] ?? 0),
            "dni" => trim($_POST["dni"] ?? ""),
            "apellidos" => trim($_POST["apellidos"] ?? ""),
            "nombres" => trim($_POST["nombres"] ?? ""),
            "correo" => trim($_POST["correo"] ?? ""),
            "categoria" => $_POST["categoria"] ?? "",
            "dedicacion" => $_POST["dedicacion"] ?? "",
        ];
    }

    // Devuelve la lista de errores; vacia si los datos son validos
    // $idActual es el docente que se edita (null al registrar), para no marcar su propio DNI como duplicado
    private function validar($datos, $idActual)
    {
        $errores = [];

        // REQ-07: la facultad debe existir (y estar activa)
        if (!$this->model->facultadActiva($datos["id_facultad"])) {
            $errores[] = "Seleccione una facultad valida.";
        }

        if (!preg_match('/^\d{8}$/', $datos["dni"])) {
            $errores[] = "El DNI debe tener exactamente 8 digitos.";
        } else {
            $existente = $this->model->porDni($datos["dni"]);
            if ($existente && (string)$existente["id"] !== (string)$idActual) {
                $errores[] = "Ya existe un docente activo con ese DNI.";
            }
        }

        if ($datos["apellidos"] === "" || mb_strlen($datos["apellidos"]) > 100) {
            $errores[] = "Los apellidos son obligatorios (maximo 100 caracteres).";
        }
        if ($datos["nombres"] === "" || mb_strlen($datos["nombres"]) > 100) {
            $errores[] = "Los nombres son obligatorios (maximo 100 caracteres).";
        }
        if (!filter_var($datos["correo"], FILTER_VALIDATE_EMAIL) || mb_strlen($datos["correo"]) > 150) {
            $errores[] = "Ingrese un correo valido (maximo 150 caracteres).";
        }

        // REQ-09 y REQ-10: solo los valores permitidos
        if (!in_array($datos["categoria"], DocenteModel::CATEGORIAS, true)) {
            $errores[] = "La categoria debe ser AUXILIAR, ASOCIADO o PRINCIPAL.";
        }
        if (!in_array($datos["dedicacion"], DocenteModel::DEDICACIONES, true)) {
            $errores[] = "La dedicacion debe ser una de las opciones de la lista.";
        }

        return $errores;
    }
}
