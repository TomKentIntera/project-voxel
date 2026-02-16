const featureColumns = [
  [
    'Free MySQL database for use with plugins',
    'nVME SSDs for blistering performance',
    'High performance Intel/AMD CPUs',
    'DDR4 RAM',
    'Unlimited slots',
  ],
  [
    'Automated daily backups for peace of mind',
    '24/7 ticket support',
    'DDoS Protection as standard',
    'Custom JAR support',
    'BungeeCord support',
  ],
  [
    'Plugin/Mod installer from common mod sites',
    'Low latency from start of the art datacenters',
    'Unlimited bandwidth',
    '1Gbps port',
    'Full SFTP access',
  ],
]

export default function Features() {
  return (
    <div className="list-features2 lighter-bg padding-bottom30 padding-top50">
      <div className="custom-width">
        <div className="row">
          <div className="main-title title-white text-center">
            <h2>All of our plans come with</h2>
          </div>
          {featureColumns.map((features, colIdx) => (
            <div className="col-sm-4" key={colIdx}>
              <div className="left-lists">
                <ul>
                  {features.map((feature, idx) => (
                    <li key={idx}>{feature}</li>
                  ))}
                </ul>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

