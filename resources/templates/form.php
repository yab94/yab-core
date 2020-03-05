<?php

if($action === null)
    $action = $request->getUri();

?><form method="<?php echo $method; ?>" action="<?php echo $action; ?>"<?php if(isset($enctype)): ?> enctype="<?php echo $enctype; ?>"<?php endif; ?>  class="form">
    <fieldset>
        <?php if(isset($title)): ?><legend><?php echo $this->html($title); ?></legend><?php endif; ?> 
        <?php if(isset($errors)): ?>
        <?php foreach($errors as $messages): ?>
            <?php foreach($messages as $message): ?>
            <p class="error"><?php echo $this->html($message); ?></p>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php foreach($fields as $field): ?>
            <?php if(in_array($field->type, array('submit', 'button'))) continue; ?>
			<div class="form-group">
				<label for="<?php echo $field->id ?? $field->name; ?>"><?php echo $this->html($field->label); ?></label>
				<?php $field->render(); ?>
			</div>
        <?php endforeach; ?>
            <p class="button">
        <?php foreach($fields as $field): ?>
            <?php if(!in_array($field->type, array('submit', 'button'))) continue; ?>
            <?php $field->render(); ?>
        <?php endforeach; ?>
            </p>
    </fieldset>
</form>
