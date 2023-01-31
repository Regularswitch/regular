import Link from "next/link";
export default function FooterComponents() {
	return (
		<footer className="sm: px-5 xl: container mx-auto text-sm lg:w-[1200px] lg:mt-[100px]">
			<div className="sm: flex justify-center flex-col xl:grid grid-cols-4 gap-20">
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
							<span className="select-none">
								<Link href="tel:+5511945408448" legacyBehavior>
									<a>+55 11 (9) 4540-8448</a>
								</Link>								
							</span>
						</li>
						<li>
							<span className="select-none">
								<Link href="mailto:contact@regularswitch.com" legacyBehavior>
									<a>contact@regularswitch.com</a>
								</Link>
							</span>
						</li>
					</ul>
				</nav>
				<nav>
					<ul>
						<li>
							<span className="select-none">Rua da consolação, 65</span>
						</li>
						<li>
							<span className="select-none">São Paulo / Brazil 01301-000</span>
						</li>
					</ul>
				</nav>
			</div>
			<br />
		</footer>
	);
}
