<?php foreach($options as $optionKey => $optionValue): ?>
    <div class="form-check form-check-inline">
        <label for="<?php echo $name; ?>_<?php echo $this->html($optionKey); ?>">
            <?php echo $this->html($optionValue); ?>
            <input 
                type="radio" 
                id="<?php echo $name; ?>_<?php echo $this->html($optionKey); ?>"
                name="<?php echo $name; ?>" 
                value="<?php echo $this->html($optionKey); ?>"
                <?php if($optionKey == $value): ?> checked="checked"<?php endif; ?>
            />
        </label>
    </div>
<?php endforeach; ?>
