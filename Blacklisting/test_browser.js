const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({ headless: "new", args: ['--no-sandbox'] });
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('BROWSER LOG:', msg.text()));
  page.on('pageerror', err => console.log('BROWSER ERROR:', err.toString()));
  
  await page.goto('http://localhost:4200/admin/application/6');
  
  await new Promise(r => setTimeout(r, 5000)); // wait for network
  
  const bodyHTML = await page.evaluate(() => document.body.innerHTML);
  console.log('HTML SNIPPET:', bodyHTML.substring(0, 500));
  
  await browser.close();
})();
