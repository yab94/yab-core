
        <div class="table-responsive">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-8"><?php $this->partial('templates/pagination.php'); ?></div>
                        <div class="btn-group col-4">
                            <?php if($nbFilters): ?>
                                <button class="btn btn-primary" data-toggle="collapse" data-target="#table_filters">Filtres</button>
                            <?php endif; ?>
                            <?php foreach($buttons as $button): ?><?php $button->render(); ?><?php endforeach; ?>
                        </div>
                    </div>
                    <?php if($nbFilters): ?>
                    <form method="<?php echo $formFilters->method; ?>" action="<?php echo $formFilters->action; ?>" class="form-inline">
                        <div id="table_filters" class="row collapse<?php echo $isFiltered ? ' show' : ''; ?> col-12 m-0 p-0 mt-3 clearfix">
                            <div class="col-8 form-group m-0 p-0">
                                <?php foreach($fields as $field): ?><?php $field->class = 'form-control'; $field->render(); ?><?php endforeach; ?>
                            </div>
                            <div class="col-4 btn-group m-0 p-0 ">
                                <input class="btn btn-primary" type="submit" value="Filtrer" />
                                <a class="btn btn-primary" href="<?php echo $resetUrl; ?>">Remise à zéro</a>
                            </div>
                        </div> 
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <table class="table table-bordered table-striped mt-3" id="dataTable" width="100%" cellspacing="0">
            <thead class="bg-gray-700">
                <?php foreach($headers as $header): ?>
                <th><?php echo $header; ?></th>
                <?php endforeach; ?>
            </thead>
            <tbody>
            <?php foreach($statement as $line): ?>
            <tr>
                <?php foreach($line as $col): ?>  
                <td><?php echo $col; ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody> 
        </table>
        </div>
