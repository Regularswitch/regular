import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import Logo from "../public/logo-blanc.svg";
import imageMenu from "../public/menu.png";
import translate, { getCookie, setCookie } from "./Translate";
import { useRouter } from 'next/router'

type headerProps = {
	lang?: string
}

export default function HeaderComponents({ lang, isLight = false }: any) {
	const [menu, SetMenu] = useState(false);
	const [language, setLang] = useState('PT');
	let textColor = isLight ? 'text-white' : 'text-black';
	let lightLogo = isLight ? '' : ' invert';
	const lightLang = isLight ? ' bg-[#FFF2] ' : ' bg-[#0002] ';
	const lightLangHover = isLight ? ' hover:bg-[#FFF3] ' : ' hover:bg-[#0002] ';

	function toggleMenu() {
		SetMenu(!menu)
	}

	const router = useRouter()
	const currentPath = router.asPath;
	const forceLightmode = currentPath.includes('/project/sesc-paulista') || currentPath.includes('/project/cine-joia');

	useEffect(function () {
		let L = getCookie('language')
		setLang(L)
	}, [])

	function Lang() {
		let selectLanguage = language
		return <>
			<div className="hidden xl:flex justify-center gap-4">
				{['EN', 'PT'].map(L => <span key={L}
					className={
						"flex cursor-pointer rounded w-[28px] h-[28px] justify-center items-center hover:bg-[#FFF3] " + lightLangHover +
						(selectLanguage == L && "bg-[#FFF2] " + lightLang)

					}
					onClick={_ => {
						setCookie('language', L)
						loadLang()
					}}
				>
					{L}
				</span>)}
			</div>
		</>
	}

	const prefix = language == 'PT' ? 'PT' : '';

	function loadLang() {
		let fullUrl = router.asPath
		fullUrl = fullUrl.replace('/PT', '')
		let nowLanguage = getCookie('language')
		if (nowLanguage == 'PT') {
			fullUrl = '/PT' + fullUrl
		}
		setTimeout(() => {
			router.replace(fullUrl)
		}, 1000)

	}

	return (
		<header className={`bg-gradient-to-b`}>

			<div className={"mx-auto lg:w-[1200px] px-5 pt-5  lg:pb-8"}>
				<header>
					<div className=" flex justify-between xl:grid grid-cols-4 text-[15px] leading-[20px]">
						<nav className="flex ">
							<Link href={"/" + prefix} legacyBehavior>
								<Image
									src={Logo}
									alt="RSW"
									className={"w-20 h-8 cursor-pointer" + (forceLightmode ? 'invert' : lightLogo)}
								/>
							</Link>
						</nav>
						<nav className="sm:flex items-center flex lg:hidden lx:hidden">
							<div className="">
								<Image
									src={imageMenu}
									alt="menu"
									className={"w-5 h-5 cursor-pointer" + lightLogo}
									onClick={toggleMenu}
								/>
							</div>
						</nav>
						<nav className={"sm: hidden lg:flex justify-center" + (forceLightmode ? 'text-white' : textColor)}>
							<ul>
								<li>
									<span>
										<Link href="https://goo.gl/maps/XkwhrcMz1mZ3oKAz7" legacyBehavior>
											<a target="_blank" rel="noopener noreferrer" className={(forceLightmode ? 'text-white' : textColor)}>
												São Paulo / Brazil
											</a>
										</Link>
									</span>
								</li>
								<li>
									<Link href="tel:+5511945408448" legacyBehavior>
										<a className={(forceLightmode ? 'text-white' : textColor)}><span>+55 (11) 9 4540-8448</span></a>
									</Link>
								</li>
								<li>
									<Link href="mailto:contact@regularswitch.com" legacyBehavior>
										<a className={(forceLightmode ? 'text-white' : textColor)}>contact@regularswitch.com</a>
									</Link>
								</li>
							</ul>
						</nav>
						<nav className="sm: hidden lg:flex justify-center">
							<ul>
								<li>
									<Link href="/" legacyBehavior>
										<a className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>Home Page</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/work'} legacyBehavior>
										<a className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>Selected works </a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/education'} legacyBehavior>
										<a className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>Education</a>
									</Link>
								</li>
							</ul>
						</nav>
						<nav className="sm: hidden lg:flex justify-center">
							<ul>
								<li>
									<Link href={'/' + prefix + '/about'} legacyBehavior>
										<a className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>{translate('About', language)}</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/contact-3'} legacyBehavior>
										<a className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>{translate('Contact', language)}</a>
									</Link>
								</li>
								<li>
									<Link href="https://www.instagram.com/regular.switch" legacyBehavior>
										<a target="_blank" rel="noopener noreferrer" className={"hover:opacity-70 " + (forceLightmode ? 'text-white' : textColor)}>Instagram</a>
									</Link>
								</li>
							</ul>
						</nav>

					</div>

					<div className={"relative " + (!menu && 'hidden')}>
						<nav className="fixed z-50 inset-0 bg-black text-[33px] text-white">
							<span
								onClick={toggleMenu}
								className="absolute right-8 top-4 text-white text-[33px]"
							>
								x
							</span>
							<ul className="fixed left-5 bottom-14" onClick={toggleMenu}>
								<li>
									<Link href="/" legacyBehavior>
										<a>Home Page</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/work'} legacyBehavior>
										<a>Selected works</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/education'} legacyBehavior>
										<a>Education</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/about'} legacyBehavior>
										<a>{translate('About', language)}</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/contact-3'} legacyBehavior>
										<a>{translate('Contact', language)}</a>
									</Link>
								</li>
								<li>
									<Link href="https://www.instagram.com/regular.switch" legacyBehavior>
										<a target="_blank" rel="noopener noreferrer" >Instagram</a>
									</Link>
								</li>
							</ul>
						</nav>
					</div>
				</header>
			</div>
		</header>
	);
}
