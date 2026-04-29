const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({ headless: "new" });
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  page.on('requestfailed', request => {
      console.log('REQUEST FAILED:', request.url(), request.failure() ? request.failure().errorText : '');
  });

  await page.goto('https://pujani.kdkhost.com.br/admin/login', { waitUntil: 'networkidle2' });
  
  await page.type('input[name="email"]', 'marcelobradrj@gmail.com');
  await page.type('input[name="password"]', '83388601Mm...');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForNavigation({ waitUntil: 'networkidle2' })
  ]);
  
  console.log('Logged in to Dashboard');
  await page.goto('https://pujani.kdkhost.com.br/admin/calendar', { waitUntil: 'networkidle2' });
  console.log('On Calendar page');
  
  const hasCalendarDiv = await page.evaluate(() => document.querySelector('#admin-calendar') !== null);
  console.log('Has calendar div:', hasCalendarDiv);
  
  const fullCalendarExists = await page.evaluate(() => typeof window.FullCalendar !== 'undefined');
  console.log('FullCalendar exists in window:', fullCalendarExists);
  
  await browser.close();
})();
