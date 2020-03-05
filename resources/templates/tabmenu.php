<ul class="nav nav-tabs">
	<?php foreach($tabmenu as $url => $label): ?>
    <li class="nav-item"><a class="nav-link<?php if($url == $currentUrl): ?> active<?php endif; ?>" href="<?php echo $url; ?>"><?php echo $this->html($label); ?></a></li>
    <?php endforeach; ?>
</ul>