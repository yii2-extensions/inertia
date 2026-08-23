<?php

declare(strict_types=1);

use PHPForge\Inertia\Page;
use yii\helpers\Html;

/**
 * @var string $id
 * @var Page $page
 * @var string $pageJson
 * @var string $title
 */
?>
<div id="<?= Html::encode($id) ?>">
    <title><?= Html::encode($title) ?></title>
    <script type="application/json"><?= $pageJson ?></script>
</div>
