export default function FooterComponents() {
	return (
		<footer className="sm: px-5 xl: container mx-auto text-sm">
			<div className="sm: flex justify-center flex-col xl:grid grid-cols-4">
				<nav className="sm: mb-8">
					<ul>
						<li>
							<span className="select-none">© 2023 Regularswitch</span>
						</li>
						<li>
							<span className="select-none">all righis reserved.</span>
						</li>
					</ul>
				</nav>
				<nav className="sm: mb-8">
					<ul>
						<li>
							<span className="select-none">+55 11 (9) 45408448</span>
						</li>
						<li>
							<span className="select-none">contact@regularswitch.com</span>
						</li>
					</ul>
				</nav>
				<nav>
					<ul>
						<li>
							<span className="select-none">Rua da consolação, 65</span>
						</li>
						<li>
							<span className="select-none">São Paulo / Brasil 01301-000</span>
						</li>
					</ul>
				</nav>
			</div>
			<br />
		</footer>
	);
}
