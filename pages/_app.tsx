import React from 'react';
import Head from 'next/head';
import '../styles/globals.css';
import type { AppProps } from 'next/app';

function App({ Component, pageProps }: AppProps) {
  return (
    <>
      <Head>
        <script type="application/ld+json">
          {`
            {
              "@context": "http://schema.org",
              "@type": "Organization",
              "name": "RSW Regular Switch",
              "url": "https://regularswitch.com/",
              "contactPoint": [
                {
                  "@type": "ContactPoint",
                  "telephone": "+55 11 94540-8448",
                  "contactType": "customer service"
                }
              ]
            }
          `}
        </script>
      </Head>
      
      <Component {...pageProps} />
    </>
  );
}

export default App;
