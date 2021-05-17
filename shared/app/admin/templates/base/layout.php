<!-- Based on https://demo.adminkit.io/ -->

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="preconnect" href="https://fonts.gstatic.com">

	<title>BFO Admin</title>

	<link rel="stylesheet" href="/cnadm/css/admin-kit.css">
	<script src="/cnadm/js/admin-kit.js"></script>

	<script src="/cnadm/js/jquery-1.12.4.min.js" language="JavaScript" type="text/javascript"></script>
	<script src="/cnadm/js/admin-main.js" language="JavaScript" type="text/javascript"></script>

	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<style>
		body {
			font-family: ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji", sans-serif !important;			
		}
		table {
			font-size: 0.9em;
		}

	</style>
</head>

<body>
	<div class="wrapper">
		<?php $this->insert('base/nav-sidebar', ['current_page' => $current_page]) ?>

		<div class="main">

			<?php $this->insert('base/nav-header') ?>

			<main class="content">
				<div class="container-fluid p-0">

					<?= $this->section('content') ?>

				</div>
			</main>

		</div>
	</div>

</body>

</html>