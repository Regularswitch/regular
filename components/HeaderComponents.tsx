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

export default function HeaderComponents({ lang, isLight }: any) {
	const [menu, SetMenu] = useState(false)
	const [language, setLang] = useState('PT')

	function toggleMenu() {
		SetMenu(!menu)
	}

	const router = useRouter()

	useEffect(function () {
		let L = getCookie('language')
		setLang(L)
	}, [])

	const lightGradient = isLight ? ' from-[#EEE] ' : ' from-[#000] '
	const lightLogo = isLight ? ' invert ' : ''
	const lightLang = isLight ? ' bg-[#0002] ' : ''
	const lightLangHover = isLight ? ' hover:bg-[#0002] ' : ''

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
		<header className={"bg-gradient-to-b"+ lightGradient}>
			
			<div className={"mx-auto lg:w-[1200px] px-5 pt-5  lg:pb-8" }>
				<header>
					<div className=" sm: flex justify-between xl:grid grid-cols-5 text-[15px] leading-[20px]">
						<nav className="flex ">
							<Link href={"/" + prefix} legacyBehavior>
								<Image
									src={Logo}
									alt="RSW"
									className={"w-20 h-8 cursor-pointer" + lightLogo}
								/>
							</Link>
						</nav>
						<nav className=" sm:flex items-center flex md:hidden lx:hidden">
							<div className="">
								<Image
									src={imageMenu}
									alt="menu"
									className="w-5 h-5 cursor-pointer"
									onClick={toggleMenu}
								/>
							</div>
						</nav>
						<nav className="sm: hidden xl:flex justify-center">
							<ul>
								<li>
									<span>São Paulo / Brazil</span>
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
									<Link href={'/' + prefix + '/work'}  legacyBehavior>
										<a className="hover:opacity-70">Selected works </a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/branding'} legacyBehavior>
										<a className="hover:opacity-70">Branding</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/digital-and-internet'} legacyBehavior>
										<a className="hover:opacity-70">Digital exprirence</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/graphical-arquitecture'} legacyBehavior>
										<a className="hover:opacity-70">Graphic architecture</a>
									</Link>
								</li>
							</ul>
						</nav>
						<nav className="sm: hidden xl:flex justify-center">
							<ul>
								<li>
									<Link href={'/' + prefix + '/about'} legacyBehavior>
										<a className="hover:opacity-70">{translate('About', language)}</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/contact-3'} legacyBehavior>
										<a className="hover:opacity-70">{translate('Contact', language)}</a>
									</Link>
								</li>
								<li>
									<Link href="https://www.instagram.com/regular.switch" legacyBehavior>
										<a className="hover:opacity-70">Instagram</a>
									</Link>
								</li>
							</ul>
						</nav>
						{/* <Lang /> */}
					</div>
					<div className={"relative " + (!menu && 'hidden')}>
						<nav className="fixed z-50 inset-0 bg-black text-[33px]">
							<span
								onClick={toggleMenu}
								className="absolute right-8 top-4 text-white text-[33px]"
							>
								x
							</span>
							<ul className="fixed left-5 bottom-14">
								<li>
									<Link href={'/' + prefix + '/work'} legacyBehavior>
										<a>Selected works</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/branding'} legacyBehavior>
										<a>Branding</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/digital-and-internet'} legacyBehavior>
										<a>Digital exprirence</a>
									</Link>
								</li>
								<li>
									<Link href={'/' + prefix + '/graphical-arquitecture'} legacyBehavior>
										<a>Graphic architecture</a>
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
										<a>Instagram</a>
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
