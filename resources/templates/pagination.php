
<?php if(!$displayPages): return; endif; ?>
<nav aria-label="Page navigation example">
	<ul class="pagination justify-content-center">
		<li class="page-item<?php if($page == $firstPage): ?> disabled<?php endif; ?>"><a class="page-link" href="<?php echo $firstPageUrl; ?>">&lt;&lt;</a></li>
		<li class="page-item<?php if($page == $previousPage): ?> disabled<?php endif; ?>"><a class="page-link" href="<?php echo $previousPageUrl; ?>">&lt;</a></li>
		<?php for($i = $page - $displayPages; $i < $page; $i++): ?>
			<?php if(0 < $i): ?>
				<li class="page-item"><a class="page-link" href="<?php echo $getPageUrl($i); ?>"><?php echo $i; ?></a></li>
			<?php endif; ?>
		<?php endfor; ?> 
		<li class="page-item disabled"><a class="page-link" href="#"><?php echo $page; ?></a></li>
		<?php for($i = $page + 1; $i < $page + $displayPages; $i++): ?>
			<?php if($i < $lastPage): ?>
				<li class="page-item"><a class="page-link" href="<?php echo $getPageUrl($i); ?>"><?php echo $i; ?></a></li>
			<?php endif; ?>
		<?php endfor; ?> 
		<li class="page-item<?php if($page == $nextPage): ?> disabled<?php endif; ?>"><a class="page-link" href="<?php echo $nextPageUrl; ?>">&gt;</a></li>
		<li class="page-item<?php if($page == $lastPage): ?> disabled<?php endif; ?>"><a class="page-link" href="<?php echo $lastPageUrl; ?>">&gt;&gt;</a></li>
	</ul>
</nav>

