<?php foreach($options as $optionKey => $optionValue): ?>
    <div class="form-check">
		<input 
			type="<?php echo $type; ?>" 
			name="<?php echo $name; ?>" 
			id="<?php echo $id; ?>"
			<?php if(isset($size)): ?>size="<?php echo $size; ?>" <?php endif; ?>
			<?php if(isset($maxlength)): ?>maxlength="<?php echo $maxlength; ?>"<?php endif; ?>
			value="<?php echo $this->html($optionKey); ?>" 
			<?php if($optionKey == $value): ?> checked="checked"<?php endif; ?>
		/>
		<label for="<?php if(isset($id)): ?><?php echo $id; ?><?php else: ?><?php echo $name; ?><?php endif; ?>"><?php echo $this->html($optionValue); ?></label>
	</div>
<?php endforeach; ?>

