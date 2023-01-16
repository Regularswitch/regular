import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import Logo from "../public/logo-blanc.svg";
import menu from "../public/menu.png";
import { getCookie, setCookie } from "./Translate";

type headerProps = {
	lang?: string
}

export default function HeaderComponents({lang}: headerProps) {
	const [eventClick, setEventClick] = useState(0)

	function Lang() {
		let selectLanguage = lang || 'PT'
		return <>
			<div className="hidden xl:flex justify-center gap-4">
				{['EN', 'PT'].map(L => <span key={L}
					className={
						"flex cursor-pointer rounded w-[28px] h-[28px] justify-center items-center hover:bg-[#FFF3] " +
						(selectLanguage == L && "bg-[#FFF2] ")

					}
					onClick={_ => setCookie('language', L)}
				>
					{L}
				</span>)}
			</div>
		</>
	}

	return (
		<header>
			<div className="mx-auto px-5 pt-5 bg-gradient-to-b from-[#000] lg:pb-8">
				<header>
					<div className=" sm: flex justify-between xl:grid grid-cols-5">
						<nav className="flex justify-center">
							<Link href="/" legacyBehavior>
								<Image
									src={Logo}
									alt="RSW"
									className="w-20 h-8 cursor-pointer"
								/>
							</Link>
						</nav>
						<nav className=" sm:flex items-center flex md:hidden lx:hidden">
							<div className="">
								<Image
									src={menu}
									alt="menu"
									className="w-5 h-5 cursor-pointer"
									onClick={() => setEventClick(eventClick + 1)}
								/>
							</div>
						</nav>
						<nav className="sm: hidden xl:flex justify-center">
							<ul>
								<li>
									<span>São Paulo </span>
								</li>
								<li>
									<Link href="tel:+5511945408448" legacyBehavior>
										<a><span>+55 11 (9) 4540-8448</span></a>
									</Link>
								</li>
								<li>
									<Link href="mailto:contact@regularswitch.com" legacyBehavior>
										<a>contact@regularswitch.com</a>
									</Link>
								</li>
							</ul>
						</nav>
						<nav className="sm: hidden xl:flex justify-center">
							<ul>
								<li>
									<Link href="/work" legacyBehavior>
										<a>Selected works</a>
									</Link>
								</li>
								<li>
									<Link href="/branding" legacyBehavior>
										<a>Branding</a>
									</Link>
								</li>
								<li>
									<Link href="digital-and-internet" legacyBehavior>
										<a>Digital exprirence</a>
									</Link>
								</li>
								<li>
									<Link href="graphical-arquitecture" legacyBehavior>
										<a>Graphic architecture</a>
									</Link>
								</li>
							</ul>
						</nav>
						<nav className="sm: hidden xl:flex justify-center">
							<ul>
								<li>
									<Link href="/about" legacyBehavior>
										<a>Sobre</a>
									</Link>
								</li>
								<li>
									<Link href="/contact-3" legacyBehavior>
										<a>Contato</a>
									</Link>
								</li>
								<li>
									<Link href="https://www.instagram.com/regular.switch" legacyBehavior>
										<a>Instagram</a>
									</Link>
								</li>
							</ul>
						</nav>
						<Lang />
					</div>
					<div className="relative hidden">
						<nav className="fixed z-10 inset-0 bg-black text-[33px]">
							<ul className="fixed left-5 bottom-14">
								<li>Trabalhos selecionados</li>
								<li>Marca</li>
								<li>Experiencia digital</li>
								<li>Arquitetura gráfica</li>
								<li>Pagina inicial</li>
								<li>Pagina inicial</li>
								<li>Pagina inicial</li>
							</ul>
						</nav>
					</div>
				</header>
			</div>
		</header>
	);
}
