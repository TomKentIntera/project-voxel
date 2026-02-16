import { useState } from 'react'
import { useFaqs } from '../../hooks/useFaqs'

/**
 * Accordion FAQ section.
 *
 * @param {object}  props
 * @param {boolean} [props.showTitle=true]    - Show the h3 heading above the accordion.
 * @param {boolean} [props.homepageOnly=true] - When true only homepage FAQs are fetched.
 */
export default function FAQ({ showTitle = true, homepageOnly = true }) {
  const { faqs, isLoading } = useFaqs({ homepageOnly })
  const [activeIndex, setActiveIndex] = useState(null)

  const toggleFaq = (index) => {
    setActiveIndex(activeIndex === index ? null : index)
  }

  if (isLoading) return null

  return (
    <div className="faq padding-bottom50 padding-top50">
      <div className="custom-width">
        {showTitle && <h3>Frequently Asked Questions</h3>}
        <div className="accordion">
          {faqs.map((faq, index) => (
            <div className="accordion-item" key={index}>
              <a
                className={activeIndex === index ? 'active' : ''}
                onClick={() => toggleFaq(index)}
              >
                {faq.title}
              </a>
              <div
                className={`content ${activeIndex === index ? 'active' : ''}`}
              >
                <p dangerouslySetInnerHTML={{ __html: faq.content }} />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
