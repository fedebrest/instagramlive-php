<?php
include_once 'utils.php';
define("autoArchive", in_array("-a", $argv), in_array("--auto-archive", $argv));

Utils::log("Espera mientras la línea de comando garantiza que el script del directo se inicie correctamente!");
sleep(2);
Utils::log("Línea de comandos disponible. Escribe \"help\" para mostrar lista de comandos.");
newCommand();


function newCommand()
{
    print "\n> ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if ($line == 'ecomments') {
        sendRequest("ecomments", null);
        Utils::log("Comentarios activados!");
    } elseif ($line == 'dcomments') {
        sendRequest("dcomments", null);
        Utils::log("Comentarios desactivados!");
    } elseif ($line == 'stop' || $line == 'end') {
        fclose($handle);
        $archived = "yes";
        if (!autoArchive) {
            Utils::log("Quieres mantener la transmisión visible durante 24h?");
            print "> ";
            $handle = fopen("php://stdin", "r");
            $archived = trim(fgets($handle));
        }
        if ($archived == 'yes') {
            sendRequest("end", ["yes"]);
        } else {
            sendRequest("end", ["no"]);
        }
        Utils::log("Saliendo de la línea de comandos. La transmisión debería finalizar.");
        sleep(2);
        exit();
    } elseif ($line == 'pin') {
        fclose($handle);
        Utils::log("Introduce el comentario que quieras fijar.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $commentId = trim(fgets($handle));
        //TODO add comment id length check
        Utils::log("Suponiendo que se tratara de una identificación de comentario válida, ¡el comentario debería estar anclado.");
        sendRequest("pin", [$commentId]);
    } elseif ($line == 'unpin') {
        Utils::log("Comprueba la otra ventana para comprobar si el comentario se ha desanclado.");
        sendRequest("unpin", null);
    } elseif ($line == 'pinned') {
        Utils::log("Comprueba la otra ventana para ver el comentario anclado.");
        sendRequest("pinned", null);
    } elseif ($line == 'comment') {
        fclose($handle);
        Utils::log("Comprueba la otra ventana para ver el comentario anclado.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $text = trim(fgets($handle));
        Utils::log("Comentado! ¡Compruebe la otra ventana para asegurarse de que se hizo el comentario!");
        sendRequest("comment", [$text]);
    } elseif ($line == 'url') {
        Utils::log("Comprueba la otra ventana para ver la URL de tu transmisión!");
        sendRequest("url", null);
    } elseif ($line == 'key') {
        Utils::log("Comprueba la otra ventana para ver tu clave de transmisión!");
        sendRequest("key", null);
    } elseif ($line == 'info') {
        Utils::log("Por favor revisa la otra ventana para ver la información de la transmisión!");
        sendRequest("info", null);
    } elseif ($line == 'viewers') {
        Utils::log("Comprueba la otra ventana para ver tu lista de espectadores!");
        sendRequest("viewers", null);
    } elseif ($line == 'questions') {
        Utils::log("Revisa la otra ventana para ver la lista de preguntas.");
        sendRequest("questions", null);
    } elseif ($line == 'showquestion') {
        fclose($handle);
        Utils::log("Introduce la id de la pregunta que te gustaría mostrar.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $questionId = trim(fgets($handle));
        Utils::log("Comprueba la otra ventana para asegurarte de que se haya mostrado la pregunta.");
        sendRequest('showquestion', [$questionId]);
    } elseif ($line == 'hidequestion') {
        Utils::log("Comprueba la otra ventana para asegurarte de que se eliminó la pregunta.");
        sendRequest('hidequestion', null);
    } elseif ($line == 'wave') {
        fclose($handle);
        Utils::log("Introduce el usuario al que le gustaría saludar.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $viewerId = trim(fgets($handle));
        Utils::log("Comprueba la otra ventana para asegurarte de que la persona fue saludada!");
        sendRequest('wave', [$viewerId]);
    } elseif ($line == 'help') {
        Utils::log("Commands:\n
        help - Muestra ayuda de comandos\n
        url - Muestra la URL\n
        key - Muestra la clave de transmisión\n
        info - Muestra información del stream\n
        viewers - Muestra los espectadores\n
        ecomments - Habilita comentarios\n
        dcomments - Deshabilita comentarios\n
        pin - Fija un comentario\n
        unpin - Deja de fijar un comentario\n
        pinned - Obtiene el comentario fijado actualmente\n
        comment - Comentarios en directo\n
        questions - Muestra todas las preguntas de la transmisión\n
        showquestion - Muestra la pregunta en el direto\n
        hidequestion - Oculta la pregunta que se muestra si se muestra una\n
        wave - NO DISPONIBLE - Saludos a usuarios\n
        stop - Detiene el directo");
    } else {
        Utils::log("Invalid Command. Type \"help\" for help!");
    }
    fclose($handle);
    newCommand();
}

function sendRequest(string $cmd, $values)
{
    /** @noinspection PhpComposerExtensionStubsInspection */
    file_put_contents(__DIR__ . '/request', json_encode([
        'cmd' => $cmd,
        'values' => isset($values) ? $values : [],
    ]));
    Utils::log("Espera un poco mientras el script recibe la solicitud");
    sleep(2);
}