<?php $this->layout('template', ['title' => 'Admin - View Prop Templates']) ?>

<?php foreach($bookies as $bookie): ?>

	<b><?=$this->e($bookie['bookie']->getName())?></b><br />
	<table class="genericTable">
		<tr>
			<th>Template ID</th>
			<th>Template / Neg Template</th>
			<th>Proptype ID</th>
			<th>Field type</th>
			<th>Last used</th>
		</tr>

	<?php foreach($bookie['templates'] as $template): ?>
		
		<tr>
			<td><b><?=$template->getID()?></b></td>
			<td><?=$template->toString()?></td>
			<td><b><?=$template->getPropTypeID()?></b></td>
			<td>e.g: <?=$template->getFieldsTypeAsExample() ?></td>
			<td><?=$template->getLastUsedDate()?></td>
			<td><input type="submit" data-li="<?=$template->getID()?>" value="Delete" disabled></td>
		</tr>
	
	<?php endforeach ?>
	</table><br />	

<?php endforeach ?>