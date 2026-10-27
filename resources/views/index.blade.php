<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ $general->app_name }} is the one account for data, airtime, electricity tokens, cable TV and exam pins. Wholesale prices, automated delivery, and a wallet that settles in seconds.">

    <title>{{ $general->app_name }} | One Wallet for Data, Airtime, Power &amp; Bills</title>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="assets/images/g7-icon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/g7-icon-180.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">

    <!-- Icons -->
    <link rel="stylesheet" href="assets/css/font-awesome.css">

    <link rel="stylesheet" href="assets/css/home.css?v=2">
</head>

<body>

    <!-- ================= ANNOUNCE STRIP ================= -->
    <div class="announce">
        <div class="run">
            <span><i class="fa-solid fa-bolt"></i> Orders clear in seconds — no queue, no waiting room</span>
            <span><i class="fa-solid fa-tags"></i> Wholesale data rates on MTN, Airtel, Glo &amp; 9mobile</span>
            <span><i class="fa-solid fa-shield-alt"></i> Failed order? Your money is back before you ask</span>
            <span><i class="fa-solid fa-headset"></i> Humans on support, 24 hours a day</span>
            <span><i class="fa-solid fa-bolt"></i> Orders clear in seconds — no queue, no waiting room</span>
            <span><i class="fa-solid fa-tags"></i> Wholesale data rates on MTN, Airtel, Glo &amp; 9mobile</span>
            <span><i class="fa-solid fa-shield-alt"></i> Failed order? Your money is back before you ask</span>
            <span><i class="fa-solid fa-headset"></i> Humans on support, 24 hours a day</span>
        </div>
    </div>

    <!-- ================= NAV ================= -->
    <nav class="nav" id="nav">
        <div class="shell">
            <a href="{{ url('/') }}" class="logo" aria-label="{{ $general->app_name }} home">
                <img src="assets/images/g7-logo.png" alt="{{ $general->app_name }} logo">
                <span>{{ $general->app_name }}</span>
            </a>

            <ul class="nav-links">
                <li><a href="#services">What we sell</a></li>
                <li><a href="#steps">Getting started</a></li>
                <li><a href="#plans">Data prices</a></li>
                <li><a href="#agent">Agents</a></li>
                <li>
                    <a href="javascript:void(0)">Company <i class="fa-solid fa-angle-down"></i></a>
                    <ul class="sub">
                        <li><a href="#edge">Why we're different</a></li>
                        <li><a href="#wallet">The wallet</a></li>
                        <li><a href="#voices">Customer stories</a></li>
                        <li><a href="#faq">Questions</a></li>
                    </ul>
                </li>
            </ul>

            <div class="nav-cta">
                <a href="{{ env('APP_URL') . '/auth/login' }}" class="txt-link">Sign in</a>
                <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-blue btn-xs">Create account</a>
                <button type="button" class="hamb" id="open-menu" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
        <span id="bar"></span>
    </nav>

    <!-- ================= HERO ================= -->
    <header class="hero" id="top">
        <div class="shell">
            <div class="hero-split">
                <div class="hero-copy">
                    <div class="badge-row" data-r>
                        <span class="live"><i></i> Systems running · orders going out now</span>
                        <span class="rating-mini"><span class="st">★★★★★</span> <b>4.9</b> from everyday users</span>
                    </div>

                    <h1 data-r style="--d:.06s">
                        Cheaper<br>
                        <span class="roll"><span id="roll">data bundles</span></span><br>
                        delivered <span class="hl">while you wait.</span>
                    </h1>

                    <p class="hero-note" data-r style="--d:.14s">
                        {{ $general->app_name }} is one account for the things you top up every week —
                        <b>data, airtime, meter tokens, decoder renewals and exam pins</b> — priced near wholesale
                        and pushed through by machines, not by hand.
                    </p>

                    <div class="hero-do" data-r style="--d:.2s">
                        <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-blue">Create a free account <i class="fa-solid fa-arrow-right"></i></a>
                        <a href="#plans" class="btn btn-ghost">See today's data prices</a>
                    </div>

                    <div class="hero-meta" data-r style="--d:.26s">
                        <div><b>&lt; 10s</b><span>Typical delivery time</span></div>
                        <div><b>15+</b><span>Services in one place</span></div>
                        <div><b>0</b><span>Naira to open an account</span></div>
                    </div>
                </div>

                <div class="art" data-r="zoom" style="--d:.16s">
                    <span class="dots" aria-hidden="true"></span>
                    <span class="disc" aria-hidden="true"></span>
                    <span class="ring" aria-hidden="true"></span>

                    <img class="art-photo" src="assets/images/2.png" alt="A happy {{ $general->app_name }} customer after a successful top-up">

                    <div class="pop pop-1">
                        <span class="ic"><i class="fa-solid fa-bolt"></i></span>
                        <div><b>10GB delivered</b><small>MTN · 4 seconds ago</small></div>
                    </div>
                    <div class="pop pop-2">
                        <span class="ic"><i class="fa-solid fa-arrow-trend-up"></i></span>
                        <div><b>&#8358;1,240 earned</b><small>Referral bonus this week</small></div>
                    </div>
                    <div class="pop pop-3">
                        <span class="ic"><i class="fa-solid fa-lightbulb"></i></span>
                        <div><b>Meter token sent</b><small>Ikeja Electric · just now</small></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= MARQUEE BAND ================= -->
    <div class="band" aria-hidden="true">
        <div class="run">
            <span>Buy data</span><span>Top up airtime</span><span>Pay light bills</span>
            <span>Renew your decoder</span><span>Check your result</span><span>Turn airtime into cash</span>
            <span>Buy data</span><span>Top up airtime</span><span>Pay light bills</span>
            <span>Renew your decoder</span><span>Check your result</span><span>Turn airtime into cash</span>
        </div>
    </div>

    <!-- ================= NETWORK STRIP ================= -->
    <section class="netstrip">
        <div class="shell">
            <h6 data-r>Wired directly into the networks and billers you already use</h6>
            <ul data-r style="--d:.08s">
                <li><img src="assets/images/mtn.png" alt="">MTN</li>
                <li><img src="assets/images/airtel-banner.png" alt="">Airtel</li>
                <li><img src="assets/images/glo-banner.png" alt="">Glo</li>
                <li><img src="assets/images/9mobile.png" alt="">9mobile</li>
                <li><img src="assets/images/ikeja.jpg" alt="">Ikeja Electric</li>
                <li><img src="assets/images/ibedc.png" alt="">IBEDC</li>
                <li><img src="assets/images/monnify.png" alt="">Monnify</li>
            </ul>
        </div>
    </section>

    <!-- ================= FIGURES ================= -->
    <section class="figures">
        <div class="shell">
            <div class="grid">
                <div class="fig" data-r>
                    <b><span data-to="500">0</span><span class="u">K+</span></b>
                    <span>Orders pushed through the engine</span>
                </div>
                <div class="fig" data-r style="--d:.08s">
                    <b><span data-to="99.9" data-dp="1">0</span><span class="u">%</span></b>
                    <span>Uptime measured over the last year</span>
                </div>
                <div class="fig" data-r style="--d:.16s">
                    <b><span data-to="36">0</span><span class="u"> states</span></b>
                    <span>Customers topping up nationwide</span>
                </div>
                <div class="fig" data-r style="--d:.24s">
                    <b><span data-to="24">0</span><span class="u">/7</span></b>
                    <span>Support that answers at 3am too</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= SERVICES (BENTO) ================= -->
    <section class="block block-soft" id="services">
        <div class="shell">
            <div class="head mid" data-r>
                <span class="tag">The catalogue</span>
                <h2>Six things you buy often,<br><u>one place to buy them</u></h2>
                <p>No hopping between apps, no bank queues, no recharge card scratching. Fund the wallet once and spend it on whatever the day throws at you.</p>
            </div>

            <div class="mosaic">
                <div class="cell m-7 c-blue" data-r>
                    <div class="inner">
                        <div>
                            <span class="ic"><i class="fa-solid fa-signal"></i></span>
                            <h3>Data &amp; airtime on all four networks</h3>
                            <p>SME, corporate gifting and direct bundles for MTN, Airtel, Glo and 9mobile — at rates most resellers can't touch. Send it to your own line or to anyone who needs it.</p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="more">Start buying <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <img class="shot" src="assets/images/choose-1.png" alt="" loading="lazy">
                    </div>
                </div>

                <div class="cell m-5 c-sun" data-r style="--d:.05s">
                    <span class="ic"><i class="fa-solid fa-lightbulb"></i></span>
                    <h3>Electricity tokens</h3>
                    <p>Prepaid or postpaid, every disco covered. Your token lands on the screen the second payment clears — no queue at the office, no photocopied receipt.</p>
                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="more">Pay a bill <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="cell m-4 c-coral" data-r style="--d:.05s">
                    <span class="ic"><i class="fa-solid fa-satellite-dish"></i></span>
                    <h3>Cable TV renewals</h3>
                    <p>DStv, GOtv, Startimes and friends — back on air before the next episode starts.</p>
                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="more">Renew now <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="cell m-4 c-green" data-r style="--d:.1s">
                    <span class="ic"><i class="fa-solid fa-arrow-right-arrow-left"></i></span>
                    <h3>Airtime to cash</h3>
                    <p>Stuck with airtime you'll never finish? Swap it for real money at rates that don't punish you.</p>
                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="more">Convert airtime <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="cell m-4 c-grape" data-r style="--d:.15s">
                    <span class="ic"><i class="fa-solid fa-scroll"></i></span>
                    <h3>Exam checker pins</h3>
                    <p>WAEC, NECO and NABTEB pins on demand — results night doesn't have to be stressful.</p>
                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="more">Get a pin <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="cell m-12 c-ink" data-r style="--d:.2s">
                    <div class="inner">
                        <div>
                            <span class="ic"><i class="fa-solid fa-terminal"></i></span>
                            <h3>Build on top of us — the developer API</h3>
                            <p>Clean REST endpoints, documentation written by people who've had to read documentation, and sandbox keys you get the same day. Resell every service on this page inside your own app and keep the margin.</p>
                        </div>
                        <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-green">Get API access <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= STEPS ================= -->
    <section class="block" id="steps">
        <div class="shell">
            <div class="flow">
                <div>
                    <div class="head" data-r="left">
                        <span class="tag t-green">From stranger to first order</span>
                        <h2>Three steps,<br>and about <em>ninety seconds</em></h2>
                        <p>There is no application to fill, no document to upload, no waiting for approval. You are one form away from your first bundle.</p>
                    </div>

                    <div class="flow-list">
                        <div class="step" data-r="left" style="--d:.06s">
                            <div class="num">01</div>
                            <div>
                                <h4>Claim your account</h4>
                                <p>Your name, an email and a phone number. That's the whole form — free forever, with no monthly charge waiting around the corner.</p>
                                <span class="mini"><i class="fa-solid fa-check"></i> Takes about 40 seconds</span>
                            </div>
                        </div>
                        <div class="step" data-r="left" style="--d:.12s">
                            <div class="num">02</div>
                            <div>
                                <h4>Move money into the wallet</h4>
                                <p>A dedicated account number is generated for you on sign-up. Transfer to it from any bank app and the balance reflects immediately — card and USSD work too.</p>
                                <span class="mini"><i class="fa-solid fa-check"></i> Reflects instantly, no confirmation calls</span>
                            </div>
                        </div>
                        <div class="step" data-r="left" style="--d:.18s">
                            <div class="num">03</div>
                            <div>
                                <h4>Buy, pay and get paid back</h4>
                                <p>Send data, clear a light bill, renew the decoder. Then drop your invite link in a group chat and collect a bonus every time someone joins and funds up.</p>
                                <span class="mini"><i class="fa-solid fa-check"></i> Referral bonus credited automatically</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flow-side" data-r="right">
                    <div class="frame"><img src="assets/images/3.png" alt="{{ $general->app_name }} dashboard on a phone" loading="lazy"></div>
                    <div class="flow-quote">
                        <p>"I opened the account during a lecture break and had sent data to my mum before the lecturer walked back in."</p>
                        <small>— Blessing O., a student in Ilorin</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= PLANS ================= -->
    <section class="block block-sky" id="plans">
        <div class="shell">
            <div class="head mid" data-r>
                <span class="tag t-sun">Live price list</span>
                <h2>What a bundle actually costs you</h2>
                <p>These are the real numbers charged at checkout — no service fee bolted on at the last screen, no surprise rounding. Pick a network to compare.</p>
            </div>

            <div class="tabs" data-r>
                <button class="tab live-tab" data-pane="net-mtn"><img src="assets/images/mtn-banner.png" alt="">MTN</button>
                <button class="tab" data-pane="net-airtel"><img src="assets/images/airtel-banner.png" alt="">Airtel</button>
                <button class="tab" data-pane="net-glo"><img src="assets/images/glo-banner.png" alt="">Glo</button>
                <button class="tab" data-pane="net-9mobile"><img src="assets/images/mobile-banner.png" alt="">9mobile</button>
            </div>

            <div class="pane live-pane" id="net-mtn">
                <div class="plan-box" data-r>
                    <div class="plan-head">
                        <span>Bundle</span><span>Volume</span><span>Validity</span><span>Price</span><span></span>
                    </div>
                    <div class="plan-scroll">
                        @foreach ($mtn as $plan)
                        <div class="plan-row">
                            <div class="plan-name">{{ $plan->plan_name }}<small>MTN</small></div>
                            <div class="plan-size"><i class="fa-solid fa-database"></i>{{ $plan->plan_size }}</div>
                            <div class="plan-size"><i class="fa-regular fa-clock"></i>{{ $plan->plan_day }}</div>
                            <div class="plan-cost">&#8358;{{ $plan->smart }}</div>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-ink btn-xs">Buy</a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pane" id="net-airtel">
                <div class="plan-box">
                    <div class="plan-head">
                        <span>Bundle</span><span>Volume</span><span>Validity</span><span>Price</span><span></span>
                    </div>
                    <div class="plan-scroll">
                        @foreach ($airtel as $plan)
                        <div class="plan-row">
                            <div class="plan-name">{{ $plan->plan_name }}<small>Airtel</small></div>
                            <div class="plan-size"><i class="fa-solid fa-database"></i>{{ $plan->plan_size }}</div>
                            <div class="plan-size"><i class="fa-regular fa-clock"></i>{{ $plan->plan_day }}</div>
                            <div class="plan-cost">&#8358;{{ $plan->smart }}</div>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-ink btn-xs">Buy</a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pane" id="net-glo">
                <div class="plan-box">
                    <div class="plan-head">
                        <span>Bundle</span><span>Volume</span><span>Validity</span><span>Price</span><span></span>
                    </div>
                    <div class="plan-scroll">
                        @foreach ($glo as $plan)
                        <div class="plan-row">
                            <div class="plan-name">{{ $plan->plan_name }}<small>Glo</small></div>
                            <div class="plan-size"><i class="fa-solid fa-database"></i>{{ $plan->plan_size }}</div>
                            <div class="plan-size"><i class="fa-regular fa-clock"></i>{{ $plan->plan_day }}</div>
                            <div class="plan-cost">&#8358;{{ $plan->smart }}</div>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-ink btn-xs">Buy</a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pane" id="net-9mobile">
                <div class="plan-box">
                    <div class="plan-head">
                        <span>Bundle</span><span>Volume</span><span>Validity</span><span>Price</span><span></span>
                    </div>
                    <div class="plan-scroll">
                        @foreach ($mobile as $plan)
                        <div class="plan-row">
                            <div class="plan-name">{{ $plan->plan_name }}<small>9mobile</small></div>
                            <div class="plan-size"><i class="fa-solid fa-database"></i>{{ $plan->plan_size }}</div>
                            <div class="plan-size"><i class="fa-regular fa-clock"></i>{{ $plan->plan_day }}</div>
                            <div class="plan-cost">&#8358;{{ $plan->smart }}</div>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-ink btn-xs">Buy</a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="plan-note" data-r><i class="fa-solid fa-circle-info"></i> Prices refresh the moment the networks move theirs. Log in to see the exact rate tied to your account tier.</p>
        </div>
    </section>

    <!-- ================= WALLET ================= -->
    <section class="block" id="wallet">
        <div class="shell">
            <div class="vault">
                <div class="vault-art" data-r="left">
                    <div class="frame"><img src="assets/images/1.png" alt="{{ $general->app_name }} wallet screen" loading="lazy"></div>
                    <div class="vault-chip">
                        <span class="ic"><i class="fa-solid fa-building-columns"></i></span>
                        <div><b>Your own account number</b><small>Transfer in from any bank</small></div>
                    </div>
                </div>

                <div data-r="right">
                    <div class="head">
                        <span class="tag t-grape">The wallet</span>
                        <h2>Money that moves<br>as fast as you do</h2>
                        <p>One balance sits behind every service, so paying is a tap instead of a card form. Every kobo in and out is written down the instant it happens.</p>
                    </div>

                    <div style="margin-top:26px">
                        <div class="check">
                            <span class="tick"><i class="fa-solid fa-check"></i></span>
                            <div><h6>Funded by bank transfer, card or USSD</h6><p>Your dedicated account number is created the day you sign up. Send money to it and the balance updates in real time.</p></div>
                        </div>
                        <div class="check">
                            <span class="tick"><i class="fa-solid fa-check"></i></span>
                            <div><h6>A receipt for everything</h6><p>Filter by date, service or network. Every order carries a reference you can quote to support.</p></div>
                        </div>
                        <div class="check">
                            <span class="tick"><i class="fa-solid fa-check"></i></span>
                            <div><h6>Refunds happen on their own</h6><p>If an order can't be delivered, the system reverses it back to your wallet without you opening a ticket.</p></div>
                        </div>
                        <div class="check">
                            <span class="tick"><i class="fa-solid fa-check"></i></span>
                            <div><h6>Locked down properly</h6><p>Transaction PINs, encrypted sessions and payment partners that are licensed and audited.</p></div>
                        </div>
                    </div>

                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-blue">Set up my wallet <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= EDGE ================= -->
    <section class="block block-mint" id="edge">
        <div class="shell">
            <div class="head mid" data-r>
                <span class="tag t-coral">Why bother switching</span>
                <h2>Cheap is easy to promise.<br><em>Reliable is the hard part.</em></h2>
                <p>Plenty of platforms will quote you a low price. Fewer will still be delivering at 2am on a public holiday.</p>
            </div>

            <div class="edge">
                <div class="edge-card" data-r>
                    <span class="n">01</span>
                    <h5>Prices set close to the bone</h5>
                    <p>We buy in volume and pass most of it on. The discount shows up on the bundle itself, not as a coupon you have to remember.</p>
                </div>
                <div class="edge-card" data-r style="--d:.06s">
                    <span class="n">02</span>
                    <h5>Nobody touches your order</h5>
                    <p>Requests go straight from your screen to the network. No agent to wake up, no manual queue to sit in overnight.</p>
                </div>
                <div class="edge-card" data-r style="--d:.12s">
                    <span class="n">03</span>
                    <h5>Support that speaks like a person</h5>
                    <p>WhatsApp, email or live chat — around the clock, and never a script read back at you.</p>
                </div>
                <div class="edge-card" data-r style="--d:.06s">
                    <span class="n">04</span>
                    <h5>Built to stay up</h5>
                    <p>Redundant routes to every network mean one provider going down doesn't take your order with it.</p>
                </div>
                <div class="edge-card" data-r style="--d:.12s">
                    <span class="n">05</span>
                    <h5>Room to grow into</h5>
                    <p>Start as a personal user, move up to agent, plug into the API when your own customers arrive.</p>
                </div>
                <div class="edge-card" data-r style="--d:.18s">
                    <span class="n">06</span>
                    <h5>Nothing hidden in the small print</h5>
                    <p>No dormancy fees, no withdrawal charges, no surprise deductions. What the price list says is what leaves your wallet.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= AGENT ================= -->
    <section class="block" id="agent">
        <div class="shell">
            <div class="agent" data-r="zoom">
                <div class="agent-grid">
                    <div>
                        <span class="tag on-dark">The agent programme</span>
                        <h2>Turn your phone into<br>a <u>neighbourhood business</u></h2>
                        <p>Every street has people who would rather hand cash to someone they know than fight with a bank app. Be that person. You serve them, we handle the plumbing, and a commission drops into your wallet on every single order.</p>
                        <p>It is also a first business for a lot of our agents — students, school leavers and side-hustlers learning pricing, customer service and cash flow with something small enough to fail safely and real enough to matter.</p>

                        <ul class="agent-perks">
                            <li>
                                <span class="ic"><i class="fa-solid fa-coins"></i></span>
                                <div><h6>Commission on every order</h6><small>Credited the moment the order completes</small></div>
                                <span class="pc">Instant</span>
                            </li>
                            <li>
                                <span class="ic"><i class="fa-solid fa-chart-column"></i></span>
                                <div><h6>No cap on what you earn</h6><small>Serve five customers or five hundred</small></div>
                                <span class="pc">Unlimited</span>
                            </li>
                            <li>
                                <span class="ic"><i class="fa-solid fa-graduation-cap"></i></span>
                                <div><h6>Learn the business as you run it</h6><small>Pricing, records and customers — hands on</small></div>
                                <span class="pc">Free</span>
                            </li>
                        </ul>

                        <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-green">Apply to become an agent <i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <div class="agent-art">
                        <div class="frame"><img src="assets/images/agent.png" alt="{{ $general->app_name }} agent serving a customer" loading="lazy"></div>
                        <div class="agent-flag">
                            <b>1%</b>
                            <small>of every referral's first deposit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= VOICES ================= -->
    <section class="block block-soft" id="voices">
        <div class="shell">
            <div class="head mid" data-r>
                <span class="tag t-sun">Straight from the group chat</span>
                <h2>What people say when<br>we're not listening</h2>
                <p>Reviews collected from users across the country — the good, and the honestly-rated.</p>
            </div>

            <div class="voices">
                <div class="voice" data-r>
                    <div class="st">★★★★★</div>
                    <q>Data used to be the thing I dreaded buying. Now it takes less time than typing this review, and I've never once had to chase an order. The price is the reason I came; the speed is why I stayed.</q>
                    <footer>
                        <img src="assets/images/woman2.jpeg" alt="Funke A.">
                        <div><h6>Funke A.</h6><small>Ibadan · buys data weekly</small></div>
                    </footer>
                </div>

                <div class="voice" data-r style="--d:.05s">
                    <div class="st">★★★★★</div>
                    <q>I converted airtime to cash for the first time expecting drama. Money hit my wallet before I finished reading the confirmation message. The rate was fair too.</q>
                    <footer>
                        <img src="assets/images/man.jpeg" alt="Chuka E.">
                        <div><h6>Chuka E.</h6><small>Onitsha · airtime to cash</small></div>
                    </footer>
                </div>

                <div class="voice" data-r style="--d:.1s">
                    <div class="st">★★★★★</div>
                    <q>Buying units used to mean leaving the house and standing in a line. My token now arrives before I've put the phone down. The charges are honest, which is rarer than it should be.</q>
                    <footer>
                        <img src="assets/images/woman.jpg" alt="Hauwa S.">
                        <div><h6>Hauwa S.</h6><small>Kano · prepaid electricity</small></div>
                    </footer>
                </div>

                <div class="voice" data-r style="--d:.05s">
                    <div class="st">★★★★☆</div>
                    <q>We shipped the integration in one afternoon. Documentation made sense, the sandbox behaved, and our own customers noticed the difference the same week. Would like webhooks to be a little chattier, but it works.</q>
                    <footer>
                        <img src="assets/images/man2.jpeg" alt="Tunde B.">
                        <div><h6>Tunde B.</h6><small>Port Harcourt · API developer</small></div>
                    </footer>
                </div>

                <div class="voice" data-r style="--d:.1s">
                    <div class="st">★★★★★</div>
                    <q>Light bill, decoder, data for three phones — all from one balance. I haven't opened my bank app for any of it in months.</q>
                    <footer>
                        <img src="assets/images/man.jpeg" alt="Ngozi U.">
                        <div><h6>Ngozi U.</h6><small>Owerri · everyday user</small></div>
                    </footer>
                </div>

                <div class="voice" data-r style="--d:.15s">
                    <div class="st">★★★★★</div>
                    <q>I started as an agent with ₦5,000 in the wallet. Six months later it pays my transport and my data, and I actually understand how a small business works now.</q>
                    <footer>
                        <img src="assets/images/woman2.jpeg" alt="Aisha M.">
                        <div><h6>Aisha M.</h6><small>Kaduna · campus agent</small></div>
                    </footer>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= APP BAND ================= -->
    <section class="block" style="padding-bottom:0">
        <div class="shell">
            <div class="appband" data-r="zoom">
                <div>
                    <span class="tag on-green">Pocket edition</span>
                    <h2>The whole platform,<br>on the phone in your hand</h2>
                    <p>Same wallet, same prices, fewer taps. Buy on the bus, pay a bill from the queue, check a transaction while the kettle boils.</p>
                    <div class="stores">
                        <a href="https://play.google.com/store/apps/details?id=com.e3datanation&pli=1" target="_parent" rel="noopener"><img src="assets/images/google_play_store.svg" alt="Get it on Google Play"></a>
                        <a href="#" target="_parent"><img src="assets/images/ios_app_store.svg" alt="Download on the App Store"></a>
                    </div>
                </div>
                <div class="appband-art">
                    <img src="assets/images/3.png" alt="{{ $general->app_name }} mobile app" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FAQ ================= -->
    <section class="block" id="faq">
        <div class="shell">
            <div class="head mid" data-r>
                <span class="tag t-green">Before you ask</span>
                <h2>The questions we get<br>every single day</h2>
            </div>

            <div class="faq-cols">
                <div>
                    <div class="q opened" data-r>
                        <button type="button">Is my money and data actually safe here? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>Yes. Traffic between you and us is encrypted, payments run through licensed gateways only, and your wallet is protected by a transaction PIN that never leaves your head. We don't store card details on our servers.</p></div>
                    </div>
                    <div class="q" data-r style="--d:.05s">
                        <button type="button">Which codes check your data balance? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>MTN [SME] → *461*4#
MTN [CG] → *460*260#
MTN [SME2] → *460*260#
MTN [Coupon] → *323*4#
9mobile [CG] → *323#
Airtel [CG] → *323#
Glo [CG] → *127*0#</p></div>
                    </div>
                    <div class="q" data-r style="--d:.1s">
                        <button type="button">An order failed. Now what? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>Nothing, usually — the system reverses the amount to your wallet on its own, normally within minutes. If the reversal hasn't shown up, send the transaction reference to support and we'll trace it straight away.</p></div>
                    </div>
                </div>

                <div>
                    <div class="q" data-r style="--d:.05s">
                        <button type="button">How does the referral bonus pay out? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>Share your personal invite link. When someone signs up through it and funds their wallet for the first time, 1% of that deposit is credited to you automatically. There's no limit on how many people you can invite.</p></div>
                    </div>
                    <div class="q" data-r style="--d:.1s">
                        <button type="button">Can I see everything I've ever bought? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>Your dashboard keeps a complete history — every recharge, bill, token and conversion, with its reference, timestamp and status. It updates the moment an order is placed and you can filter it by service or date.</p></div>
                    </div>
                    <div class="q" data-r style="--d:.15s">
                        <button type="button">Does it cost anything to open an account? <span class="sign"><i class="fa-solid fa-plus"></i></span></button>
                        <div class="ans"><p>Not a naira. Registration is free, keeping the account is free, and there are no dormancy or maintenance charges. You only ever pay for what you buy.</p></div>
                    </div>
                </div>
            </div>

            <div class="faq-help" data-r>
                <div>
                    <h5>Still stuck on something?</h5>
                    <p>Reach us on {{ $general->app_phone }}, email {{ $general->app_email }}, or open the chat — day or night.</p>
                </div>
                <a href="{{ $general->app_whatsapp }}" class="btn btn-blue"><i class="fa-brands fa-whatsapp"></i> Chat with support</a>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="foot">
        <div class="shell">

            <div class="foot-grid">
                <div class="foot-brand">
                    <span class="logo"><img src="assets/images/welcome.png" alt="{{ $general->app_name }}"> {{ $general->app_name }}</span>
                    <p>The everyday top-up platform for Nigerians who are tired of paying more and waiting longer than they should.</p>
                    <div class="social">
                        <a href="{{ $general->instagram }}" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="{{ $general->tiktok }}" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="{{ $general->app_whatsapp }}" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="{{ $general->facebook }}" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    </div>
                </div>

                <div>
                    <h6>Services</h6>
                    <ul class="foot-list">
                        <li><a href="#services">Data &amp; airtime</a></li>
                        <li><a href="#services">Electricity tokens</a></li>
                        <li><a href="#services">Cable TV</a></li>
                        <li><a href="#services">Airtime to cash</a></li>
                        <li><a href="#services">Exam pins</a></li>
                    </ul>
                </div>

                <div>
                    <h6>Explore</h6>
                    <ul class="foot-list">
                        <li><a href="#plans">Data prices</a></li>
                        <li><a href="#steps">Getting started</a></li>
                        <li><a href="#agent">Agent programme</a></li>
                        <li><a href="#voices">Reviews</a></li>
                        <li><a href="#faq">Questions</a></li>
                        <li><a href="{{ env('APP_URL') . '/auth/register' }}">Create account</a></li>
                    </ul>
                </div>

                <div>
                    <h6>Reach us</h6>
                    <ul class="foot-touch">
                        <li><i class="fa-solid fa-phone"></i> <a href="tel:{{ $general->app_phone }}">{{ $general->app_phone }}</a></li>
                        <li><i class="fa-solid fa-envelope"></i> <a href="mailto:{{ $general->app_email }}">{{ $general->app_email }}</a></li>
                        <li><i class="fa-solid fa-location-dot"></i> <span>{{ $general->app_address }}</span></li>
                    </ul>
                </div>
            </div>

            <div class="wordmark" aria-hidden="true">{{ $general->app_name }}</div>
        </div>

        <div class="foot-end">
            <div class="shell">
                <span>© {{ date('Y') }} {{ $general->app_name }}. All rights reserved.</span>
                <span>Built by <a href="https://adedevelopers.com" target="_blank" rel="noopener">A D E Developers</a></span>
            </div>
        </div>
    </footer>

    <button id="up" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

    <!-- ================= SLIDE-OUT MENU ================= -->
    <div class="veil" id="veil"></div>
    <aside class="panel-menu" aria-label="Mobile menu">
        <div class="pm-top">
            <a href="{{ url('/') }}" class="logo"><img src="assets/images/welcome.png" alt="{{ $general->app_name }}"> {{ $general->app_name }}</a>
            <button class="pm-close" id="close-menu" aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <ul class="pm-links">
            <li><a href="#top">Home <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#services">What we sell <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#steps">Getting started <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#plans">Data prices <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#wallet">The wallet <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#edge">Why we're different <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#agent">Agents <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#voices">Reviews <i class="fa-solid fa-arrow-right"></i></a></li>
            <li><a href="#faq">Questions <i class="fa-solid fa-arrow-right"></i></a></li>
        </ul>
        <div class="pm-cta">
            <a href="{{ env('APP_URL') . '/auth/login' }}" class="btn btn-ghost">Sign in</a>
            <a href="{{ env('APP_URL') . '/auth/register' }}" class="btn btn-blue">Create account</a>
        </div>
        <div class="pm-touch">
            <span><i class="fa-solid fa-envelope"></i> <a href="mailto:{{ $general->app_email }}">{{ $general->app_email }}</a></span>
            <span><i class="fa-solid fa-phone"></i> <a href="tel:{{ $general->app_phone }}">{{ $general->app_phone }}</a></span>
        </div>
        <div class="social">
            <a href="{{ $general->instagram }}" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="{{ $general->tiktok }}" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            <a href="{{ $general->app_whatsapp }}" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="{{ $general->facebook }}" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
        </div>
    </aside>

    <script src="assets/js/home.js?v=2"></script>
</body>
</html>
