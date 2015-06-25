<?php
include '../vendor/autoload.php';

//Use CodeReviewPrototype\App;

$codeReviewPrototype = new \CodeReviewPrototype\App\CodeReviewPrototype();

if (isset($_POST['branch'])) {
    echo '<pre>',$codeReviewPrototype->getDiff(),'</pre>';
} else {
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