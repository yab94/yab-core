<input
    type="<?php echo $type; ?>" 
    name="<?php echo $name; ?>" 
    id="<?php echo $id; ?>"
    <?php if(isset($size)): ?>size="<?php echo $size; ?>" <?php endif; ?>
    <?php if(isset($maxlength)): ?>maxlength="<?php echo $maxlength; ?>"<?php endif; ?>
    <?php if(isset($placeholder)): ?>placeholder="<?php echo $placeholder; ?>"<?php endif; ?>
    <?php if(isset($class)): ?>class="<?php echo $class; ?>"<?php endif; ?>
    value="<?php echo $this->html($value); ?>" 
/>