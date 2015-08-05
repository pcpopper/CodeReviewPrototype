<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../vendor/autoload.php';

use CodeReviewPrototype\App\Controllers\ViewsController;

new ViewsController('Default');
?>




<?php
//Use CodeReviewPrototype\App;
//
//$codeReviewPrototype = new \CodeReviewPrototype\App\CodeReviewPrototype();
//
if (isset($_POST['branch'])) {
    echo '<pre>',$codeReviewPrototype->getDiff(),'</pre>';
} else if (1==0) {
    ?>
    <form method="post">
        <select name="branch">
            <?php foreach ($codeReviewPrototype->getBranches() as $branch): ?>
                <option value="<?php echo $branch['value'] ?>" <?php echo ($branch['value'] == '') ? 'selected' : '' ?>><?php echo $branch['name'] ?></option>
            <?php endforeach ?>
        </select>
        <input type="submit">
    </form>
<?php
}
?>