<?php /** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpUndefinedConstantInspection */

set_time_limit(0);
date_default_timezone_set('America/New_York');
if (php_sapi_name() !== "cli") {
    die("This script may not be run on a website!");
}

if (!defined('PHP_MAJOR_VERSION') || PHP_MAJOR_VERSION < 7) {
    print("This script requires PHP version 7 or higher! Please update your php installation before attempting to run this script again!");
    exit();
}

//Para usar los siguientes comandos, hace falta un repositorio que ya no hay forma de conseguir...
$helpData = [];
$helpData = registerArgument($helpData, $argv, "help", "Mostrar ayuda de comandos.", "h", "help");
$helpData = registerArgument($helpData, $argv, "bypassCheck", "Omite la verificación del sistema operativo. ¡No uses esto si no sabes lo que estás haciendo!", "b", "bypass-check");
$helpData = registerArgument($helpData, $argv, "forceLegacy", "Fuerza el modo heredado para usuarios de Windows", "l", "force-legacy");
$helpData = registerArgument($helpData, $argv, "bypassCutoff", "¡No uses esto si no estás verificado! Omite el corte de emisión después de una hora.", "-bypass-cutoff");
$helpData = registerArgument($helpData, $argv, "infiniteStream", "USA ESTO SI NO ESTAS VERIFICADO: Inicia automáticamente una nuevo directo después del corte de la hora.", "i", "infinite-stream");
$helpData = registerArgument($helpData, $argv, "autoArchive", "Archiva automáticamente el directo al finalizar. Dura lo mismo que una historia, 24h.", "a", "auto-archive");
$helpData = registerArgument($helpData, $argv, "logCommentOutput", "Registra comentarios y reacciones en un archivo de texto.", "o", "comment-output");
$helpData = registerArgument($helpData, $argv, "obsAutomationAccept", "Acepta automáticamente la solicitud de OBS.", "-obs");
$helpData = registerArgument($helpData, $argv, "obsNoStream", "Inhabilita el inicio automático de la transmisión en OBS.", "-obs-no-stream");
$helpData = registerArgument($helpData, $argv, "disableObsAutomation", "Deshabilita la automatización de OBS y posteriormente deshabilita la verificación de ruta.", "-no-obs");
$helpData = registerArgument($helpData, $argv, "startDisableComments", "Inhabilita automáticamente los comandos cuando se inicia la transmisión.", "-dcomments");
$helpData = registerArgument($helpData, $argv, "useRmtps", "Este parámetro ya se ha puesto por defecto. Sólo se puede emitir por rtmps.", "-use-rmtps");
$helpData = registerArgument($helpData, $argv, "thisIsAPlaceholder", "Establece el tiempo límite del directo en segundos (Ejemplo: --stream-sec=60).", "-stream-sec");
$helpData = registerArgument($helpData, $argv, "thisIsAPlaceholder1", "Fija automáticamente un comentario. Nota: Usa guiones bajos para espacios. (Ejemplo: --auto-pin=Hello_World!).", "-auto-pin");
$helpData = registerArgument($helpData, $argv, "forceSlobs", "En caso de tener instalados OBS y StreamLabs, con este parámetro se iniciará StreamLabs", "-streamlabs-obs");
$helpData = registerArgument($helpData, $argv, "promptLogin", "Ignora el usuario y contraseña escritos en config.php y te lo pregunta al comenzar.", "p", "prompt-login");
$helpData = registerArgument($helpData, $argv, "dump", "Fuerza un volcado de error con fines de depuración.", "d", "dump");
$helpData = registerArgument($helpData, $argv, "dumpFlavor", "Vuelca el valor de la versión actual.", "-dumpFlavor");

$streamTotalSec = 0;
$autoPin = null;

foreach ($argv as $curArg) {
    if (strpos($curArg, '--stream-sec=') !== false) {
        $streamTotalSec = (int)str_replace('--stream-sec=', '', $curArg);
    }
    if (strpos($curArg, '--auto-pin=') !== false) {
        $autoPin = str_replace('_', ' ', str_replace('--auto-pin=', '', $curArg));
    }
}

//Load Utils
require 'utils.php';

define("scriptVersion", "1.4");
define("scriptVersionCode", "31");
define("scriptFlavor", "stable");
Utils::log("Loading InstagramLive-PHP v" . scriptVersion . "(Escrito por Josh Roy. Adaptado y traducido por @marcsoulmusic)");



if (dumpFlavor) {
    Utils::log(scriptFlavor);
    exit();
}

if (dump) {
    Utils::dump();
    exit();
}

if (help) {
    Utils::log("Command Line Arguments:");
    foreach ($helpData as $option) {
        $dOption = json_decode($option, true);
        Utils::log($dOption['tacks']['mini'] . ($dOption['tacks']['full'] !== null ? " (" . $dOption['tacks']['full'] . "): " : ": ") . $dOption['description']);
    }
    exit();
}

//Check for required files
Utils::existsOrError(__DIR__ . '/vendor/autoload.php', "Instagram API Files");
Utils::existsOrError('obs.php', "OBS Integration");
Utils::existsOrError('config.php', "Username & Password Storage");

//Load Classes
require __DIR__ . '/vendor/autoload.php'; //Composer
require 'obs.php'; //OBS Utils

use InstagramAPI\Instagram;
use InstagramAPI\Exception\ChallengeRequiredException;
use InstagramAPI\Request\Live;
use InstagramAPI\Response\Model\User;
use InstagramAPI\Response\Model\Comment;

class ExtendedInstagram extends Instagram
{
    public function changeUser($username, $password)
    {
        $this->_setUser($username, $password);
    }
}

require_once 'config.php';

//Run the script and spawn a new console window if applicable.
main(true, new ObsHelper(!obsNoStream, disableObsAutomation, forceSlobs), $streamTotalSec, $autoPin);

function main($console, ObsHelper $helper, $streamTotalSec, $autoPin)
{
    $username = IG_USERNAME;
    $password = IG_PASS;
    if (promptLogin) {
        Utils::log("Please enter your credentials...");
        print "Username: ";
        $usernameHandle = fopen("php://stdin", "r");
        $username = trim(fgets($usernameHandle));
        fclose($usernameHandle);
        print "Password: ";
        $passwordHandle = fopen("php://stdin", "r");
        $password = trim(fgets($passwordHandle));
        fclose($passwordHandle);
    }

    if ($username == "usuario" || $password == "contraseña") {
        Utils::log("No se ha establecido el usuario y contraseña...");
        exit();
    }

//Login to Instagram
    Utils::log("Logueando en Instagram! Por favor espera, esto puede llegar a tardar 2 minutos...");
    $ig = new ExtendedInstagram(false, false);
    try {
        $loginResponse = $ig->login($username, $password);

        if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
            Utils::log("Se requiere autenticación en dos pasos. Proporciona el código de verificación recibido en otros medios.");
            $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
            print "\nType your verification code> ";
            $handle = fopen("php://stdin", "r");
            $verificationCode = trim(fgets($handle));
            fclose($handle);
            Utils::log("Iniciar sesión con token de verificación...");
            $ig->finishTwoFactorLogin($username, $password, $twoFactorIdentifier, $verificationCode);
        }
    } catch (\Exception $e) {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            if ($e instanceof ChallengeRequiredException && $e->getResponse()->getErrorType() === 'checkpoint_challenge_required') {
                $response = $e->getResponse();

                Utils::log("Inicio de sesión sospechoso: ¿Deseas verificar tu cuenta por mensaje de texto o correo electrónico? Escribe yes o ignora.");
                Utils::log("Suspicious Login: Please only attempt this once or twice if your attempts are unsuccessful. If this keeps happening, this script is not for you");
                print "> ";
                $handle = fopen("php://stdin", "r");
                $attemptBypass = trim(fgets($handle));
                fclose($handle);
                if ($attemptBypass == 'yes') {
                    Utils::log("Preparando para verificar la cuenta...");
                    sleep(3);

                    Utils::log("Inicio de sesión sospechoso: seleccione su opción de verificación escribiendo \"sms\" o \"email\" respectivamente. De lo contrario, presiona intro para abortar.");
                    print "> ";
                    $handle = fopen("php://stdin", "r");
                    $choice = trim(fgets($handle));
                    fclose($handle);
                    if ($choice === "sms") {
                        $verification_method = 0;
                    } elseif ($choice === "email") {
                        $verification_method = 1;
                    } else {
                        Utils::log("Abortando!");
                        exit();
                    }

                    /** @noinspection PhpUndefinedMethodInspection */
                    $checkApiPath = trim(substr($response->getChallenge()->getApiPath(), 1));
                    $customResponse = $ig->request($checkApiPath)
                        ->setNeedsAuth(false)
                        ->addPost('choice', $verification_method)
                        ->addPost('_uuid', $ig->uuid)
                        ->addPost('guid', $ig->uuid)
                        ->addPost('device_id', $ig->device_id)
                        ->addPost('_uid', $ig->account_id)
                        ->addPost('_csrftoken', $ig->client->getToken())
                        ->getDecodedResponse();

                    try {
                        if ($customResponse['status'] === 'ok' && isset($customResponse['action'])) {
                            if ($customResponse['action'] === 'close') {
                                Utils::log("Inicio de sesión sospechoso: desafío de cuenta exitoso, ¡vuelve a ejecutar el script!
");
                                exit();
                            }
                        }

                        Utils::log("Ingresa el código de verificación recibido por " . ($verification_method ? 'email' : 'sms') . "...");
                        print "> ";
                        $handle = fopen("php://stdin", "r");
                        $cCode = trim(fgets($handle));
                        fclose($handle);
                        $ig->changeUser($username, $password);
                        $customResponse = $ig->request($checkApiPath)
                            ->setNeedsAuth(false)
                            ->addPost('security_code', $cCode)
                            ->addPost('_uuid', $ig->uuid)
                            ->addPost('guid', $ig->uuid)
                            ->addPost('device_id', $ig->device_id)
                            ->addPost('_uid', $ig->account_id)
                            ->addPost('_csrftoken', $ig->client->getToken())
                            ->getDecodedResponse();

                        if (@$customResponse['status'] === 'ok' && @$customResponse['logged_in_user']['pk'] !== null) {
                            Utils::log("Inicio de sesión sospechoso: desafío de cuenta exitoso, ¡vuelve a ejecutar el script!");
                            exit();
                        } else {
                            Utils::log("Suspicious Login: I have no clue if that just worked, re-run me to check.");
                            exit();
                        }
                    } catch (Exception $ex) {
                        Utils::log("Suspicious Login: Account Challenge Failed :(.");
                        Utils::dump($ex->getMessage());
                        exit();
                    }
                } else {
                    Utils::log("Suspicious Login: Account Challenge Failed :(.");
                    Utils::dump();
                    exit();
                }
            }
        } catch (\LazyJsonMapper\Exception\LazyJsonMapperException $mapperException) {
            Utils::log("Error mientras se intentaba loguear en Instagram: " . $e->getMessage());
            Utils::dump();
            exit();
        }

        Utils::log("Error mientras se intentaba loguear en Instagram " . $e->getMessage());
        Utils::dump();
        exit();
    }

//Block Responsible for Creating the Livestream.
    try {
        if (!$ig->isMaybeLoggedIn) {
            Utils::log("Error intentando loguear en Instagram: isMaybeLoggedIn fail!");
            Utils::dump();
            exit();
        }
        Utils::log("Logueado! Creando directo...");
        $stream = $ig->live->create();
        $broadcastId = $stream->getBroadcastId();

        // RTMP RTMPS connection
        $streamUploadUrl = (!useRmtps === true ? preg_replace(
            '#^rtmps://([^/]+?):443/#ui',
            'rtmps://\1:443/',
            $stream->getUploadUrl()
        ) : $stream->getUploadUrl());

        //Grab the stream url as well as the stream key.
        $split = preg_split("[" . $broadcastId . "]", $streamUploadUrl);

        $streamUrl = trim($split[0]);
        $streamKey = trim($broadcastId . $split[1]);

        $obsAutomation = true;
        if ($helper->obs_path === null) {
            Utils::log("Fallo integración OBS: No se detecta existencia de OBS!" . (!Utils::isWindows() ? " Ten en cuenta que macOS no es compatible :(" : ""));
            $obsAutomation = false;
        } else {
            if (!obsAutomationAccept) {
                Utils::log("Integración en OBS: Quieres que se abra automáticamente OBS con todo configurado para emitir? (modifica fotograma a vertical 720x1280 y pone url y clave de transmisión) Escribe \"yes\" o presiona intro para ignorar.");
                print "> ";
                $eoiH = fopen("php://stdin", "r");
                $eoi = trim(fgets($eoiH));
                fclose($eoiH);
                if ($eoi !== "yes") {
                    $obsAutomation = false;
                }
            }
        }

        Utils::log("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");

        Utils::log("======================== Clave de transmisión ========================\n" . $streamKey . "\n======================== Clave de transmisión ========================\n");

        if (!$obsAutomation) {
            if (Utils::isWindows()) {
                shell_exec("echo " . Utils::sanitizeStreamKey($streamKey) . " | clip");
                Utils::log("Windows: Tu clave de transmisión ya se ha copiado al portapapeles.");
            }
        } else {
            if ($helper->isObsRunning()) {
                Utils::log("Integración OBS: Killing OBS...");
                $helper->killOBS();
            }
            if (!$helper->attempted_settings_save) {
                Utils::log("Integración OBS: Realizando copia de seguridad de OBS basic.ini...");
                $helper->saveSettingsState();
            }
            Utils::log("Integración OBS: Cargando basic.ini con las configuraciones óptimas...");
            $helper->updateSettingsState();
            if (!$helper->attempted_service_save) {
                Utils::log("Integración OBS: Realizando copia de seguridad de OBS service.json...");
                $helper->saveServiceState();
            }
            Utils::log("Integración OBS: Rellenando service.json con nueva url & key.");
            $helper->setServiceState($streamUrl, $streamKey);
            if (!$helper->slobsPresent) {
                Utils::log("Integración OBS: reiniciando OBS...");
                $helper->spawnOBS();
                Utils::log("Integración OBS: Esperando a OBS 15 segundos...");
                if ($helper->waitForOBS()) {
                    sleep(1);
                    Utils::log("Integración OBS: OBS iniciado! comenzamos la emisión...");
                } else {
                    Utils::log("Integración OBS: No se detecta OBS! Pulsa intro si OBS está emitiendo...");
                    $oPauseH = fopen("php://stdin", "r");
                    fgets($oPauseH);
                    fclose($oPauseH);
                }
            }
        }

        if (!$obsAutomation || obsNoStream || $helper->slobsPresent) {
            Utils::log("Por favor," . ($helper->slobsPresent ? " inicia StreamLambs OBS y " : " ") . "inicia transmisión con url y clave indicadas. Cuando estés listo y emitiendo, pulsa intro.!");
            $pauseH = fopen("php://stdin", "r");
            fgets($pauseH);
            fclose($pauseH);
        }

        $ig->live->start($broadcastId);

        if (startDisableComments) {
            $ig->live->disableComments($broadcastId);
            Utils::log("Comentarios deshabilitados automáticamente");
        }

        if ($autoPin !== null) {
            $ig->live->pinComment($broadcastId, $ig->live->comment($broadcastId, $autoPin)->getComment()->getPk());
            Utils::log("Comentario fijado automáticamente");
        }

        if ((Utils::isWindows() || bypassCheck) && !forceLegacy) {
            Utils::log("Línea de comando: Windows detectado! Se abrirá una nueva consola para la entrada de comandos y esta se convertirá en una salida de comando / similar.");
            beginListener($ig, $broadcastId, $streamUrl, $streamKey, $console, $obsAutomation, $helper, $streamTotalSec, $autoPin);
        } else {
            Utils::log("Línea de comando: macOS / Linux detectado! El script ha entrado en modo heredado. Utilice Windows para todas las funciones más recientes.");
            newCommand($ig->live, $broadcastId, $streamUrl, $streamKey, $obsAutomation, $helper);
        }

        Utils::log("¡No se pueden iniciar las líneas de comando, intentando limpiar!");
        $ig->live->getFinalViewerList($broadcastId);
        $ig->live->end($broadcastId);
        Utils::dump();
        exit();
    } catch (\Exception $e) {
        echo 'Error mientras se estaba creando el directo ' . $e->getMessage() . "\n";
        Utils::dump($e->getMessage());
        exit();
    }
}

function addLike(User $user)
{
    $cmt = "@" . $user->getUsername() . " te ha dado un like!";
    Utils::log($cmt);
    if (logCommentOutput) {
        Utils::logOutput($cmt);
    }
}

function addComment(Comment $comment, bool $system = false)
{
    $cmt = ($system ? "" : ("Comentario [ID " . $comment->getPk() . "] @" . $comment->getUser()->getUsername() . ": ")) . $comment->getText();
    Utils::log($cmt);
    if (logCommentOutput) {
        Utils::logOutput($cmt);
    }
}

/**
 * @param \InstagramAPI\Response\Model\User[] $users
 */
function parseFinalViewers($users)
{
    if (logCommentOutput) {
        $finalViewers = 'Espectadores finales: ';
        foreach ($users as $user) {
            $finalViewers = $finalViewers . $user->getUsername() . ', ';
        }
        Utils::logOutput($finalViewers);
    }
}

function beginListener(Instagram $ig, $broadcastId, $streamUrl, $streamKey, $console, bool $obsAuto, ObsHelper $helper, int $streamTotalSec, $autoPin)
{
    if (bypassCheck && !Utils::isWindows()) {
        Utils::log("Línea de comando: está forzando la nueva línea de comando. Esto no es compatible y puede provocar problemas.");
        Utils::log("Línea de comando: para iniciar la nueva línea de comando, ejecute el script commandLine.php");
    } else {
        if ($console) {
            pclose(popen("start \"InstagramLive-PHP: Command Line\" \"" . PHP_BINARY . "\" commandLine.php" . (autoArchive === true ? " -a" : ""), "r"));
        }
    }
    @cli_set_process_title("InstagramLive-PHP: Live Chat & Likes");
    $lastCommentTs = 0;
    $lastLikeTs = 0;
    $lastQuestion = -1;
    $lastCommentPin = -1;
    $lastCommentPinHandle = '';
    $lastCommentPinText = '';
    $exit = false;
    $startTime = time();

    @unlink(__DIR__ . '/request');

    if (logCommentOutput) {
        Utils::logOutput(PHP_EOL . "--- New Session At Epoch: " . time() . " ---" . PHP_EOL);
    }

    do {
        /** @noinspection PhpComposerExtensionStubsInspection */

        //Check for commands
        $request = json_decode(@file_get_contents(__DIR__ . '/request'), true);
        if (!empty($request)) {
            try {
                $cmd = $request['cmd'];
                $values = $request['values'];
                if ($cmd == 'ecomments') {
                    $ig->live->enableComments($broadcastId);
                    Utils::log("Enabled Comments!");
                } elseif ($cmd == 'dcomments') {
                    $ig->live->disableComments($broadcastId);
                    Utils::log("Disabled Comments!");
                } elseif ($cmd == 'end') {
                    if ($obsAuto) {
                        Utils::log("OBS Integration: Killing OBS...");
                        $helper->killOBS();
                        Utils::log("OBS Integration: Restoring old basic.ini...");
                        $helper->resetSettingsState();
                        Utils::log("OBS Integration: Restoring old service.json...");
                        $helper->resetServiceState();
                    }
                    $archived = $values[0];
                    Utils::log("Wrapping up and exiting...");
                    //Needs this to retain, I guess?
                    parseFinalViewers($ig->live->getFinalViewerList($broadcastId)->getUsers());
                    $ig->live->end($broadcastId);
                    if ($archived == 'yes') {
                        $ig->live->addToPostLive($broadcastId);
                        Utils::log("Livestream added to Archive!");
                    }
                    Utils::log("Ended stream!");
                    unlink(__DIR__ . '/request');
                    sleep(2);
                    exit();
                } elseif ($cmd == 'pin') {
                    $commentId = $values[0];
                    if (strlen($commentId) === 17 && //Comment IDs are 17 digits
                        is_numeric($commentId) && //Comment IDs only contain numbers
                        strpos($commentId, '-') === false) { //Comment IDs are not negative
                        $ig->live->pinComment($broadcastId, $commentId);
                        Utils::log("Pinned a comment!");
                    } else {
                        Utils::log("You entered an invalid comment id!");
                    }
                } elseif ($cmd == 'unpin') {
                    if ($lastCommentPin == -1) {
                        Utils::log("You have no comment pinned!");
                    } else {
                        $ig->live->unpinComment($broadcastId, $lastCommentPin);
                        Utils::log("Unpinned the pinned comment!");
                    }
                } elseif ($cmd == 'pinned') {
                    if ($lastCommentPin == -1) {
                        Utils::log("There is no comment pinned!");
                    } else {
                        Utils::log("Pinned Comment:\n @" . $lastCommentPinHandle . ': ' . $lastCommentPinText);
                    }
                } elseif ($cmd == 'comment') {
                    $text = $values[0];
                    if ($text !== "") {
                        $ig->live->comment($broadcastId, $text);
                        Utils::log("Commented on stream!");
                    } else {
                        Utils::log("Comments may not be empty!");
                    }
                } elseif ($cmd == 'url') {
                    Utils::log("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");
                } elseif ($cmd == 'key') {
                    Utils::log("======================== Clave de transmisión ========================\n" . $streamKey . "\n======================== Clave de transmisión ========================");
                    if (Utils::isWindows()) {
                        shell_exec("echo " . Utils::sanitizeStreamKey($streamKey) . " | clip");
                        Utils::log("Windows: Your stream key has been pre-copied to your clipboard.");
                    }
                } elseif ($cmd == 'info') {
                    $info = $ig->live->getInfo($broadcastId);
                    $status = $info->getStatus();
                    $muted = var_export($info->is_Messages(), true);
                    $count = $info->getViewerCount();
                    Utils::log("Info:\nStatus: $status \nMuted: $muted \nViewer Count: $count");
                } elseif ($cmd == 'viewers') {
                    Utils::log("Viewers:");
                    $ig->live->getInfo($broadcastId);
                    $vCount = 0;
                    foreach ($ig->live->getViewerList($broadcastId)->getUsers() as &$cuser) {
                        Utils::log("[" . $cuser->getPk() . "] @" . $cuser->getUsername() . " (" . $cuser->getFullName() . ")\n");
                        $vCount++;
                    }
                    if ($vCount > 0) {
                        Utils::log("Total Viewers: " . $vCount);
                    } else {
                        Utils::log("There are no live viewers.");
                    }
                } elseif ($cmd == 'questions') {
                    Utils::log("Questions:");
                    foreach ($ig->live->getQuestions()->getQuestions() as $cquestion) {
                        Utils::log("[ID: " . $cquestion->getQid() . "] @" . $cquestion->getUser()->getUsername() . ": " . $cquestion->getText());
                    }
                } elseif ($cmd == 'showquestion') {
                    $questionId = $values[0];
                    if (strlen($questionId) === 17 && //Question IDs are 17 digits
                        is_numeric($questionId) && //Question IDs only contain numbers
                        strpos($questionId, '-') === false) { //Question IDs are not negative
                        $lastQuestion = $questionId;
                        $ig->live->showQuestion($broadcastId, $questionId);
                        Utils::log("Displayed question!");
                    } else {
                        Utils::log("Invalid question id!");
                    }
                } elseif ($cmd == 'hidequestion') {
                    if ($lastQuestion == -1) {
                        Utils::log("There is no question displayed!");
                    } else {
                        $ig->live->hideQuestion($broadcastId, $lastQuestion);
                        $lastQuestion = -1;
                        Utils::log("Removed the displayed question!");
                    }
                } elseif ($cmd == 'wave') {
                    $viewerId = $values[0];
                    try {
                        $ig->live->wave($broadcastId, $viewerId);
                        Utils::log("Waved at a user!");
                    } catch (Exception $waveError) {
                        Utils::log("Could not wave at user! Make sure you're waving at people who are in the stream. Additionally, you can only wave at a person once per stream!");
                        Utils::dump($waveError->getMessage());
                    }
                }
                unlink(__DIR__ . '/request');
            } catch (Exception $cmdExc) {
                echo 'Error While Executing Command: ' . $cmdExc->getMessage() . "\n";
                Utils::dump($cmdExc->getMessage());
            }
        }

        //Process Comments
        $commentsResponse = $ig->live->getComments($broadcastId, $lastCommentTs); //Request comments since the last time we checked
        $systemComments = $commentsResponse->getSystemComments(); //Metric data about comments and likes
        $comments = $commentsResponse->getComments(); //Get the actual comments from the request we made
        if (!empty($systemComments)) {
            $lastCommentTs = $systemComments[0]->getCreatedAt();
        }
        if (!empty($comments) && $comments[0]->getCreatedAt() > $lastCommentTs) {
            $lastCommentTs = $comments[0]->getCreatedAt();
        }

        if ($commentsResponse->isPinnedComment()) {
            $pinnedComment = $commentsResponse->getPinnedComment();
            $lastCommentPin = $pinnedComment->getPk();
            $lastCommentPinHandle = $pinnedComment->getUser()->getUsername();
            $lastCommentPinText = $pinnedComment->getText();
        } else {
            $lastCommentPin = -1;
        }

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                addComment($comment);
            }
        }
        if (!empty($systemComments)) {
            foreach ($systemComments as $systemComment) {
                addComment($systemComment, true);
            }
        }

        //Process Likes
        $ig->live->getHeartbeatAndViewerCount($broadcastId); //Maintain :clap: comments :clap: and :clap: likes :clap: after :clap: stream
        $likeCountResponse = $ig->live->getLikeCount($broadcastId, $lastLikeTs); //Get our current batch for likes
        $lastLikeTs = $likeCountResponse->getLikeTs();
        foreach ($likeCountResponse->getLikers() as $user) {
            $user = $ig->people->getInfoById($user->getUserId())->getUser();
            addLike($user);
        }

        //Calculate Times for Limiter Argument
        if ($streamTotalSec > 0 && (time() - $startTime) >= $streamTotalSec) {
            if ($obsAuto) {
                Utils::log("OBS Integration: Killing OBS...");
                $helper->killOBS();
                Utils::log("OBS Integration: Restoring old basic.ini...");
                $helper->resetSettingsState();
                Utils::log("OBS Integration: Restoring old service.json...");
                $helper->resetServiceState();
            }
            parseFinalViewers($ig->live->getFinalViewerList($broadcastId)->getUsers());
            $ig->live->end($broadcastId);
            Utils::log("Stream has ended due to user requested stream limit of $streamTotalSec seconds!");

            $archived = "yes";
            if (!autoArchive) {
                print "Would you like to archive this stream?\n> ";
                $handle = fopen("php://stdin", "r");
                $archived = trim(fgets($handle));
                fclose($handle);
            }
            if ($archived == 'yes') {
                Utils::log("Adding to Archive...");
                $ig->live->addToPostLive($broadcastId);
                Utils::log("Livestream added to archive!");
            }
            Utils::log("Stream Ended! Please close the console window!");
            @unlink(__DIR__ . '/request');
            sleep(2);
            exit();
        }

        //Calculate Times for Hour-Cutoff
        if (!bypassCutoff && (time() - $startTime) >= 3480) {
            if ($obsAuto) {
                Utils::log("OBS Integration: Killing OBS...");
                $helper->killOBS();
                Utils::log("OBS Integration: Restoring old basic.ini...");
                $helper->resetSettingsState();
                Utils::log("OBS Integration: Restoring old service.json...");
                $helper->resetServiceState();
            }
            parseFinalViewers($ig->live->getFinalViewerList($broadcastId)->getUsers());
            $ig->live->end($broadcastId);
            Utils::log("Stream has ended due to Instagram's one hour time limit!");
            $archived = "yes";
            if (!autoArchive) {
                print "Would you like to archive this stream?\n> ";
                $handle = fopen("php://stdin", "r");
                $archived = trim(fgets($handle));
                fclose($handle);
            }
            if ($archived == 'yes') {
                Utils::log("Adding to Archive...");
                $ig->live->addToPostLive($broadcastId);
                Utils::log("Livestream added to archive!");
            }
            $restart = "yes";
            if (!infiniteStream) {
                Utils::log("Would you like to go live again?");
                print "> ";
                $handle = fopen("php://stdin", "r");
                $restart = trim(fgets($handle));
                fclose($handle);
            }
            if ($restart == 'yes') {
                Utils::log("Restarting Livestream!");
                main(false, $helper, $streamTotalSec, $autoPin);
            }
            Utils::log("Stream Ended! Please close the console window!");
            @unlink(__DIR__ . '/request');
            sleep(2);
            exit();
        }

        sleep(1);
    } while (!$exit);
}

/**
 * The handler for interpreting the commands passed via the command line.
 * @param Live $live Instagram live endpoints.
 * @param string $broadcastId The id of the live stream.
 * @param string $streamUrl The rtmp link of the stream.
 * @param string $streamKey The stream key.
 * @param bool $obsAuto True if obs automation is enabled.
 * @param ObsHelper $helper The helper class for obs utils.
 */
function newCommand(Live $live, $broadcastId, $streamUrl, $streamKey, bool $obsAuto, ObsHelper $helper)
{
    print "\n> ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    if ($line == 'ecomments') {
        $live->enableComments($broadcastId);
        Utils::log("Enabled Comments!");
    } elseif ($line == 'dcomments') {
        $live->disableComments($broadcastId);
        Utils::log("Disabled Comments!");
    } elseif ($line == 'stop' || $line == 'end') {
        fclose($handle);
        if ($obsAuto) {
            Utils::log("OBS Integration: Killing OBS...");
            $helper->killOBS();
            Utils::log("OBS Integration: Restoring old basic.ini...");
            $helper->resetSettingsState();
            Utils::log("OBS Integration: Restoring old service.json...");
            $helper->resetServiceState();
        }
        //Needs this to retain, I guess?
        parseFinalViewers($live->getFinalViewerList($broadcastId)->getUsers());
        $live->end($broadcastId);
        Utils::log("Stream Ended!");
        $archived = "yes";
        if (!autoArchive) {
            Utils::log("Would you like to keep the stream archived for 24 hours? Type \"yes\" to do so or anything else to not.");
            print "> ";
            $handle = fopen("php://stdin", "r");
            $archived = trim(fgets($handle));
            fclose($handle);
        }
        if ($archived == 'yes') {
            Utils::log("Adding to Archive!");
            $live->addToPostLive($broadcastId);
            Utils::log("Livestream added to archive!");
        }
        Utils::log("Wrapping up and exiting...");
        exit();
    } elseif ($line == 'url') {
        Utils::log("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");
    } elseif ($line == 'key') {
        Utils::log("======================== Current Stream Key ========================\n" . $streamKey . "\n======================== Current Stream Key ========================");
        if (Utils::isWindows()) {
            shell_exec("echo " . Utils::sanitizeStreamKey($streamKey) . " | clip");
            Utils::log("Windows: Your stream key has been pre-copied to your clipboard.");
        }
    } elseif ($line == 'info') {
        $info = $live->getInfo($broadcastId);
        $status = $info->getStatus();
        $muted = var_export($info->is_Messages(), true);
        $count = $info->getViewerCount();
        Utils::log("Info:\nStatus: $status\nMuted: $muted\nViewer Count: $count");
    } elseif ($line == 'viewers') {
        Utils::log("Viewers:");
        $live->getInfo($broadcastId);
        $vCount = 0;
        foreach ($live->getViewerList($broadcastId)->getUsers() as &$cuser) {
            Utils::log("[" . $cuser->getPk() . "] @" . $cuser->getUsername() . " (" . $cuser->getFullName() . ")\n");
            $vCount++;
        }
        if ($vCount > 0) {
            Utils::log("Total Viewers: " . $vCount);
        } else {
            Utils::log("There are no live viewers.");
        }
    } elseif ($line == 'wave') {
        Utils::log("Please enter the user id you would like to wave at.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $viewerId = trim(fgets($handle));
        fclose($handle);
        try {
            $live->wave($broadcastId, $viewerId);
            Utils::log("Waved at a user!");
        } catch (Exception $waveError) {
            Utils::log("Could not wave at user! Make sure you're waving at people who are in the stream. Additionally, you can only wave at a person once per stream!");
            Utils::dump($waveError->getMessage());
        }
    } elseif ($line == 'comment') {
        Utils::log("Please enter the text you wish to comment.");
        print "> ";
        $handle = fopen("php://stdin", "r");
        $text = trim(fgets($handle));
        fclose($handle);
        if ($text !== "") {
            $live->comment($broadcastId, $text);
            Utils::log("Commented on stream!");
        } else {
            Utils::log("Comments may not be empty!");
        }
    } elseif ($line == 'help') {
        Utils::log("Commands:\nhelp - Prints this message\nurl - Prints Stream URL\nkey - Prints Stream Key\ninfo - Grabs Stream Info\nviewers - Grabs Stream Viewers\necomments - Enables Comments\ndcomments - Disables Comments\ncomment - Leaves a comment on your stream\nwave - Waves at a User\nstop - Stops the Live Stream");
    } else {
        Utils::log("Invalid Command. Type \"help\" for help!");
    }
    @fclose($handle);
    newCommand($live, $broadcastId, $streamUrl, $streamKey, $obsAuto, $helper);
}


/**
 * Registers a command line argument to a global variable.
 * @param array $helpData The array which holds the command data for the help menu.
 * @param array $argv The array of arguments passed to the script.
 * @param string $name The name to be used in the global variable.
 * @param string $description The description of the argument to be used in the help menu.
 * @param string $tack The mini-tack argument name.
 * @param string|null $fullTack The full-tack argument name.
 * @return array The array of help data with the new argument.
 */
function registerArgument(array $helpData, array $argv, string $name, string $description, string $tack, string $fullTack = null): array
{
    if ($fullTack !== null) {
        $fullTack = '--' . $fullTack;
    }
    define($name, in_array('-' . $tack, $argv) || in_array($fullTack, $argv));
    /** @noinspection PhpComposerExtensionStubsInspection */
    array_push($helpData, json_encode([
        'name' => $name,
        'description' => $description,
        'tacks' => [
            'mini' => '-' . $tack,
            'full' => $fullTack
        ]
    ]));
    return $helpData;
}