describe('Share Your Steps shortcode', () => {
  it('renders the map and initializes Tracking and Chat', () => {
    cy.visit('/', {
      onBeforeLoad(win) {
        cy.spy(win.console, 'log').as('consoleLog');
      },
    });

    cy.get('.sys-map').should('exist');
    cy.get('@consoleLog').should('be.calledWith', 'Tracking initialized');
    cy.get('@consoleLog').should('be.calledWith', 'Chat initialized');
  });
});
