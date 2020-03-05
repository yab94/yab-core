<select 
    name="<?php echo $name; ?>"
    <?php if(isset($class)): ?>class="<?php echo $class; ?>"<?php endif; ?>
    <?php if(isset($onchange)): ?> onchange="<?php echo $onchange; ?>"<?php endif; ?>
>
<?php foreach($options as $optionKey => $optionValue): ?>
    <option value="<?php echo $this->html($optionKey); ?>"<?php if($optionKey == $value): ?> selected="selected"<?php endif; ?>><?php echo $this->html($optionValue); ?></option>
<?php endforeach; ?>
</select>

