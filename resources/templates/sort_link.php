<?php if($field == $sortField && $sortDir == 'DESC'): ?>&darr;&nbsp;<?php endif; ?>
<?php if($field == $sortField && $sortDir == 'ASC'): ?>&uarr;&nbsp;<?php endif; ?>
<a href="<?php echo $url; ?>"><?php echo $this->html($label); ?></a>