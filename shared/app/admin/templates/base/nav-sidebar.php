<nav id="sidebar" class="sidebar">
	<div class="sidebar-content js-simplebar">
		<a class="sidebar-brand" href="/cnadm/">
			<img style="height: 30px; width: 30px; margin-right: 10px;" src="/img/iconv2.jpg">
			<span class="align-middle">Admin</span>
		</a>

		<ul class="sidebar-nav">
			<li class="sidebar-header">
				Pages
			</li>

			<li class="sidebar-item <?=$current_page == 'home' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'manualactions' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/manualactions">
					<i class="align-middle" data-feather="user"></i> <span class="align-middle">Schedule</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'matchup_new' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/newmatchup">
					<i class="align-middle" data-feather="settings"></i> <span class="align-middle">New matchup</span>
				</a>
			</li>

			<li class="sidebar-item <?=in_array($current_page, ['events', 'event_detailed', 'fighters']) ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/events">
					<i class="align-middle" data-feather="credit-card"></i> <span class="align-middle">Events overview</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'resetchangenums' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/resetchangenums">
					<i class="align-middle" data-feather="settings"></i> <span class="align-middle">Reset changenums</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'newmatchup' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/newmatchup">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">New odds (in progress)</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'flagged_odds' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/flagged">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">View flagged</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'renamings' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/renamings">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">Event renamings</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'proptemplates' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/proptemplates">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">View prop templates</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'proptype_new' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/proptype">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">New prop type</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'proptemplate' || $current_page == 'proptemplate_new' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/proptemplate">
					<i class="align-middle" data-feather="book"></i> <span class="align-middle">New bookie prop template</span>
				</a>
			</li>

			<li class="sidebar-header">
				Logs
			</li>

			<li class="sidebar-item <?=$current_page == 'parserlogs' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/parserlogs">
					<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Parser logs</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'logs' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/logs/latest">
					<i class="align-middle" data-feather="map"></i> <span class="align-middle">Odds job log</span>
				</a>
			</li>

			<li class="sidebar-item <?=$current_page == 'changeauditlog' ? 'active' : ''?>">
				<a class="sidebar-link" href="/cnadm/log/changeaudit">
					<i class="align-middle" data-feather="map"></i> <span class="align-middle">Change audit log</span>
				</a>
			</li>

			<li class="sidebar-item">
						<a data-bs-target="#ui" data-bs-toggle="collapse" class="sidebar-link collapsed">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-briefcase align-middle"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg> <span class="align-middle">Other logs</span>
            </a>
						<ul id="ui" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar" style="">
							<li class="sidebar-item"><a class="sidebar-link" href="/cnadm/log/twitter">Twitter</a></li>
							<li class="sidebar-item"><a class="sidebar-link" href="/cnadm/log/facebook">Facebook</a></li>
							<li class="sidebar-item"><a class="sidebar-link" href="/cnadm/log/scheduleparser">Scheduleparser</a></li>
							<li class="sidebar-item"><a class="sidebar-link" href="/cnadm/log/sesmailer">SESMailer</a></li>

						</ul>
					</li>

			<li class="sidebar-header">
				Dev Components
			</li>

			<li class="sidebar-item">
				<a class="sidebar-link" href="/cnadm/index.html">
					<i class="align-middle" data-feather="map"></i> <span class="align-middle">Admin Kit</span>
				</a>
			</li>

		</ul>
		
	</div>
</nav>