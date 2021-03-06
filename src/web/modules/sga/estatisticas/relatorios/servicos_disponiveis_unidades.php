<?php

$status = function($model) {
    if ($model->getStatus() == 1) { 
        $class = 'active';
        $label = _('Ativo');
    } else {
        $class = 'inactive';
        $label = _('Inativo');
    }
    return '<span class="' . $class . '">' . $label . '</span>';
};

?>
<?php foreach ($relatorio->getDados() as $dado): ?>
<div class="header">
    <h2><?php echo $dado['unidade'] ?></h2>
</div>
<table class="ui-data-table">
    <thead>
        <tr>
            <th><?php echo _('Serviço') ?></th>
            <th><?php echo _('Situação') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($dado['servicos'] as $servico): ?>
        <tr>
            <td class="strong"><?php echo $servico->getNome() ?></td>
            <td class="w100 center"><?php echo $status($servico) ?></td>
        </tr>
        <?php if (sizeof($servico->getSubServicos())): ?>
        <tr class="sub-table">
            <td colspan="2">
                <table>
                    <tbody>
                        <?php foreach ($servico->getSubServicos() as $subServico): ?>
                        <tr>
                            <td><?php echo $subServico->getNome() ?></td>
                            <td class="w100 center"><?php echo $status($subServico) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endforeach; ?>