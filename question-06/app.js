document.addEventListener('DOMContentLoaded', () => {
  const contentContainer = document.getElementById('article-content');
  const hitMeBtn = document.getElementById('hit-me-btn');
  const buttonContainer = document.getElementById('button-container');
  const statsBadge = document.getElementById('stats');

  const initialParagraphs = Array.from(contentContainer.querySelectorAll('p'));
  const originalTemplates = initialParagraphs.map((p) => p.cloneNode(true));

  let areImagesHidden = false;

  function updateSeparators() {
    contentContainer.querySelectorAll('hr.pair-separator').forEach((hr) => hr.remove());

    const allParagraphs = Array.from(contentContainer.querySelectorAll('p'));
    const totalCount = allParagraphs.length;
    let separatorCount = 0;

    for (let i = 0; i < totalCount; i++) {
      const isPairEnd = (i + 1) % 2 === 0;
      const hasNext = i + 1 < totalCount;

      if (isPairEnd && hasNext) {
        const hr = document.createElement('hr');
        hr.className = 'pair-separator';
        allParagraphs[i].after(hr);
        separatorCount++;
      }
    }

    if (statsBadge) {
      statsBadge.textContent = `Total: ${totalCount} | Separators: ${separatorCount}`;
    }
  }

  function applyImageVisibility(p) {
    if (p.querySelector('img')) {
      p.classList.toggle('hidden-image-p', areImagesHidden);
    }
  }

  function shuffle(array) {
    const arr = [...array];
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  hitMeBtn.addEventListener('click', () => {
    const clones = originalTemplates.map((t) => t.cloneNode(true));
    shuffle(clones).forEach((p) => {
      applyImageVisibility(p);
      contentContainer.appendChild(p);
    });
    updateSeparators();
  });

  // Second button created through JS only
  const toggleBtn = document.createElement('button');
  toggleBtn.id = 'toggle-img-btn';
  toggleBtn.type = 'button';
  toggleBtn.className = 'secondary-btn';
  toggleBtn.textContent = 'Hide Images';

  toggleBtn.addEventListener('click', () => {
    areImagesHidden = !areImagesHidden;
    toggleBtn.textContent = areImagesHidden ? 'Show Images' : 'Hide Images';
    toggleBtn.classList.toggle('active', areImagesHidden);

    contentContainer.querySelectorAll('p').forEach(applyImageVisibility);
    updateSeparators();
  });

  buttonContainer.appendChild(toggleBtn);
  updateSeparators();
});
