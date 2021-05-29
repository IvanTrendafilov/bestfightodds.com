<nav class="navbar navbar-expand navbar-light navbar-bg">
	<a class="sidebar-toggle d-flex">
		<i class="hamburger align-self-center"></i>
	</a>

	<form class="d-none d-sm-inline-block">
		<div class="input-group input-group-navbar">
				<span class="text-dark"><?= date('Y-m-d H:i:s')?></span>
		</div>
	</form>

	<div class="navbar-collapse collapse">
		<ul class="navbar-nav navbar-align">

			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
					<i class="align-middle" data-feather="settings"></i>
				</a>
				<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
					<span class="text-dark">Sign out</span>
				</a>
				<div class="dropdown-menu dropdown-menu-end">
					<a class="dropdown-item" href="/cnadm/logout">Sign out</a>
				</div>
			</li>
		</ul>
	</div>
</nav>