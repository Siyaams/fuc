import fetch from "node-fetch";

// === Helpers ===
function cleanNumber(number) {
  return (number || "").replace(/\D+/g, "");
}

function getCountryFromNumber(number, flagList) {
  const cleanNum = cleanNumber(number);
  // Sort flag list by prefix length (longest first)
  flagList.sort(
    (a, b) =>
      cleanNumber(b.code || "").length - cleanNumber(a.code || "").length
  );

  for (const entry of flagList) {
    const code = cleanNumber(entry.code || "");
    if (code && cleanNum.startsWith(code)) {
      const flag = entry.emoji || "🌍";
      const country = entry.name || "Unknown";
      return `${flag} ${country}`;
    }
  }
  return "🌍 Unknown";
}

function convertToBDTime(timeStr) {
  const date = new Date(timeStr + " UTC"); // input is UTC
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Dhaka",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
    .format(date)
    .replace(",", "");
}

function extractOTP(message) {
  const match = message.match(/\b\d{3}[-\s]?\d{3}\b|\b\d{4,8}\b/);
  return match ? match[0].trim() : null;
}

// === API Handler ===
export default async function handler(req, res) {
  res.setHeader("Content-Type", "application/json; charset=utf-8");

  const from = "2025-09-14 00:13:00";
  const to = "2025-09-23 23:59:59";

  const baseUrl =
    "http://51.89.99.105/NumberPanel/agent/res/data_smscdr.php";
  const timestamp = Date.now();

  const params = new URLSearchParams({
    fdate1: from,
    fdate2: to,
    frange: "",
    fclient: "",
    fnum: "",
    fcli: "",
    fgdate: "",
    fgmonth: "",
    fgrange: "",
    fgclient: "",
    fgnumber: "",
    fgcli: "",
    fg: "0",
    sEcho: "2",
    iColumns: "9",
    sColumns: ",,,,,,,,",
    iDisplayStart: "0",
    iDisplayLength: "-1",
    mDataProp_0: "0",
    bSearchable_0: "true",
    bSortable_0: "true",
    mDataProp_1: "1",
    bSearchable_1: "true",
    bSortable_1: "true",
    mDataProp_2: "2",
    bSearchable_2: "true",
    bSortable_2: "true",
    mDataProp_3: "3",
    bSearchable_3: "true",
    bSortable_3: "true",
    mDataProp_4: "4",
    bSearchable_4: "true",
    bSortable_4: "true",
    mDataProp_5: "5",
    bSearchable_5: "true",
    bSortable_5: "true",
    mDataProp_6: "6",
    bSearchable_6: "true",
    bSortable_6: "true",
    mDataProp_7: "7",
    bSearchable_7: "true",
    bSortable_7: "true",
    mDataProp_8: "8",
    bSearchable_8: "true",
    bSortable_8: "false",
    iSortCol_0: "0",
    sSortDir_0: "desc",
    iSortingCols: "1",
    _: timestamp.toString(),
  });

  const url = `${baseUrl}?${params.toString()}`;

  try {
    // Fetch API response
    const response = await fetch(url, {
      headers: {
        "User-Agent":
          "Mozilla/5.0 (Linux; Android 11; WALPAD8G Build/RP1A.200720.011) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.7204.157 Safari/537.36",
        Accept: "application/json, text/javascript, */*; q=0.01",
        "Accept-Encoding": "gzip, deflate",
        "X-Requested-With": "XMLHttpRequest",
        Referer: "http://51.89.99.105/NumberPanel/agent/SMSCDRReports",
        "Accept-Language":
          "en-US,en;q=0.9,ar-EG;q=0.8,ar;q=0.7,fr-DZ;q=0.6,fr;q=0.5,bn-BD;q=0.4,bn;q=0.3,fr-FR;q=0.2",
        Cookie: "PHPSESSID=n7qt2lsi0ge3pvlppqj431gn1m",
      },
    });

    const data = await response.json();

    // Fetch country/flag list
    let flagList = [];
    try {
      const flagRes = await fetch("https://siyamahmmed.shop/flag.php");
      flagList = (await flagRes.json()) || [];
    } catch (e) {
      flagList = [];
    }

    const results = [];

    for (const row of data.aaData || []) {
      const time = row[0] || "";
      const number = row[2] || "";
      const platform = row[3] || "";
      const message = row[5] || "";

      if (!number || !platform || !message) continue;

      const otp = extractOTP(message);
      const country = getCountryFromNumber(number, flagList);
      const bdTime = convertToBDTime(time);

      results.push({
        id: crypto.randomUUID(),
        number,
        platform,
        country,
        time: bdTime,
        otp,
        message,
      });
    }

    res.status(200).json(results);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
}
