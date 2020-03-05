<form method="<?php echo $method; ?>" action="<?php echo $action; ?>" class="form">
	<div class="row form-inline mt-3 mb-1">

		<?php // AFFICHAGE DES INPUTS, SELECT, RADIO... ?>
		<?php foreach($inputs as $input): ?>
			<?php if(in_array($input->type, array('submit', 'button'))) continue; ?>
			<?php $input->render(); ?>
		<?php endforeach; ?>

		<div class="btn-group">
		<?php // AFFICHAGE DES SUBMITS... ?>
		<?php foreach($inputs as $input): ?>
			<?php if(!in_array($input->type, array('submit'))) continue; ?>
			<?php $input->render(); ?>
		<?php endforeach; ?>
		<?php // AFFICHAGE DES BOUTONS (LIENS, ETC ...) ?>
		<?php foreach($inputs as $input): ?>
			<?php if(!in_array($input->type, array('button'))) continue; ?>
			<?php $input->render(); ?>
		<?php endforeach; ?>
		</div>
		
	</div>
</form>