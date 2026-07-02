export default function FooterComponents() {
	return (
		<footer className="sm: px-5 xl: container mx-auto text-sm lg:w-[1200px] lg:mt-[100px]">
			<div className="sm:flex justify-center flex-col xl:grid grid-cols-4 gap-5 md:gap-10 lg:gap-10 xl:gap-20">
				<nav className="mb-8 sm:mb-0">
					<ul>
						<li>
							<span className="select-none">© 2024-25 Regularswitch</span>
						</li>
						<li>
							<span className="select-none">all rights reserved.</span>
						</li>
					</ul>
				</nav>
				<nav className="mb-8 sm:mb-0">
					<ul>
						<li>
							<span className="select-none">
								<a href="tel:+5511945408448">+55 (11) 9 4540-8448</a>
							</span>
						</li>
						<li>
							<span className="select-none">
								<a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>
							</span>
						</li>
					</ul>
				</nav>
				<nav>
					<ul>
						<li>							
							<span className="select-none">
								<a href="https://goo.gl/maps/XkwhrcMz1mZ3oKAz7" target="_blank" rel="noopener noreferrer">
									Rua da consolação, 65
								</a>
							</span>
						</li>
						<li>
							<span className="select-none">
								<a href="https://goo.gl/maps/XkwhrcMz1mZ3oKAz7" target="_blank" rel="noopener noreferrer">
									Sao Paulo / Brazil 01301-000
								</a>
							</span>
						</li>
					</ul>
				</nav>
			</div>
			<br />
		</footer>
	);
}
