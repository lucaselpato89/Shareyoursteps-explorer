describe('Example front-end', () => {
  it('loads the example domain', () => {
    cy.visit('/');
    cy.contains('Example Domain');
  });
});
