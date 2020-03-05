<input
    type="file" 
    name="<?php echo $name; ?>" 
    id="<?php echo $id; ?>"
    <?php if(isset($placeholder)): ?>placeholder="<?php echo $placeholder; ?>"<?php endif; ?>
    <?php if(isset($class)): ?>class="<?php echo $class; ?>"<?php endif; ?>
/>