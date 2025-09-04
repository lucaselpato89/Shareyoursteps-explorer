const fs = require('fs');
const path = require('path');
const { minify } = require('terser');

const dir = path.join(__dirname, 'wp-content/plugins/share-your-steps/assets/js');

(async () => {
  const files = fs.readdirSync(dir).filter(f => f.endsWith('.js') && !f.endsWith('.min.js'));
  for (const file of files) {
    const filePath = path.join(dir, file);
    const code = fs.readFileSync(filePath, 'utf8');
    const transformed = code.replace(/from\s+(['"])(\.\/[^'"\n]+)\.js\1/g, 'from $1$2.min.js$1');
    const result = await minify(transformed, { module: true });
    fs.writeFileSync(path.join(dir, file.replace('.js', '.min.js')), result.code, 'utf8');
  }
})();
