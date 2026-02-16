const features = [
  {
    icon: 'fa fa-envelope',
    title: 'Reliability',
    description:
      'We maintain a 99.9% uptime on all of our servers and take a proactive approach to hardware and maintenance.',
  },
  {
    icon: 'fa fa-rocket',
    title: 'High Performance',
    description:
      "All of our servers run on high performance hardware including DDR4 RAM and nVME SSDs. We don't compromise on performance!",
  },
  {
    icon: 'fa fa-comments',
    title: 'Excellent Support',
    description:
      "Running your own server comes with its challenges. We're on hand to assist with technical questions 365 days a year.",
  },
  {
    icon: 'fa fa-microchip',
    title: 'Mod/plugin installer',
    description:
      'Our panel allows you to quickly and easily install plugins and mods for your server from popular plugin/mod repositories.',
  },
  {
    icon: 'fa fa-hdd',
    title: 'Unlimited SSD space',
    description:
      "We offer unlimited* SSD space with all of our Minecraft servers so you'll never run out of space for worlds and mods.",
  },
  {
    icon: 'fa fa-server',
    title: 'SFTP Access',
    description:
      'We provide SFTP access to all of our servers so you can upload your own Jars and mods, or download your world for your own backups.',
  },
  {
    icon: 'fa fa-database',
    title: 'Automated backups',
    description:
      'All of our servers are backed up automatically on a daily schedule, so you\'ll never have to worry about losing your hard work!',
  },
  {
    icon: 'fa fa-exchange-alt',
    title: 'Unlimited Slots',
    description:
      "Unlike other hosts we don't limit the number of slots you can have. You're free to let as many players on as the server can handle!",
  },
]

export default function FeaturesMain() {
  const firstRow = features.slice(0, 4)
  const secondRow = features.slice(4, 8)

  return (
    <div className="features-six padding-bottom50 padding-top50">
      <div className="custom-width">
        <div className="main-title text-center">
          <h2>Why choose Intera?</h2>
          <p>
            There are hundreds of Minecraft server hosts out there - so why
            choose Intera Games?
          </p>
        </div>
        <div className="row">
          {firstRow.map((feature) => (
            <div className="col-sm-3" key={feature.title}>
              <div className="text-container">
                <div className="text">
                  <div className="img-content">
                    <i className={feature.icon}></i>
                  </div>
                  <h4>{feature.title}</h4>
                  <p>{feature.description}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="row">
          {secondRow.map((feature) => (
            <div className="col-sm-3" key={feature.title}>
              <div className="text-container">
                <div className="text">
                  <div className="img-content">
                    <i className={feature.icon}></i>
                  </div>
                  <h4>{feature.title}</h4>
                  <p>{feature.description}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="buttons mb-20">
          <a href="/plans" className="btn btn-green btn-large">
            Get started <i className="fas fa-long-arrow-alt-right"></i>
          </a>
        </div>
        <div className="mt-20">
          <p className="text-small text-center">* Subject to fair use</p>
        </div>
      </div>
    </div>
  )
}

