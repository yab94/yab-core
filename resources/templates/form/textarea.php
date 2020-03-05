<textarea 
    <?php if(isset($cols)): ?>cols="<?php echo $cols; ?>" <?php endif; ?>
    <?php if(isset($rows)): ?>rows="<?php echo $rows; ?>" <?php endif; ?>
    <?php if(isset($size)): ?>size="<?php echo $size; ?>" <?php endif; ?>
    <?php if(isset($maxlength)): ?>maxlength="<?php echo $maxlength; ?>"<?php endif; ?>
    name="<?php echo $name; ?>" 
    <?php if(isset($class)): ?>class="<?php echo $class; ?>"<?php endif; ?>
><?php echo $this->html($value); ?></textarea>