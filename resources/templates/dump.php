<?php if(isset($dumpTitle)): ?><li><?php echo $dumpTitle; ?></li><?php endif; ?>
<ul>
<?php foreach($data as $key => $value): ?>
    <?php if(is_array($value)): ?>
    <?php $this->partial('templates/dump.php', array('dumpTitle' => $key, 'data' => $value)); ?>
    <?php else: ?>
    <li><?php echo $key; ?>: <?php echo $value; ?></li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>